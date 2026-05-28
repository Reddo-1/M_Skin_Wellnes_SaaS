import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { CurrencyPipe, DatePipe, DecimalPipe } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { ProductService, ProductData } from '../../../../core/services/product.service';
import { ProductStockService } from '../../../../core/services/product-stock.service';
import { StockMovementService, StockEntryData } from '../../../../core/services/stock-movement.service';
import { AuthService } from '../../../../core/services/auth.service';
import { NotificationService } from '../../../../core/services/notification.service';
import { Product } from '../../../../core/models/product.model';
import { StockMovement } from '../../../../core/models/stock-movement.model';
import { PaginatedMeta } from '../../../../core/models/paginated.model';
import { GENERIC_ERROR, loadResourceError } from '../../../../core/utils/form.util';
import { AlertComponent } from '../../../../shared/ui/alert/alert.component';
import { NormalizePipe } from '../../../../shared/pipes/normalize.pipe';
import { ProductModalComponent, ProductFormValue } from '../modals/product-modal/product-modal.component';
import { StockEntryModalComponent, StockEntryFormValue } from './modals/stock-entry-modal/stock-entry-modal.component';
import { StockAdjustModalComponent, StockAdjustFormValue } from './modals/stock-adjust-modal/stock-adjust-modal.component';

@Component({
  selector: 'app-product-detail',
  standalone: true,
  imports: [
    CurrencyPipe,
    DatePipe,
    DecimalPipe,
    RouterLink,
    AlertComponent,
    NormalizePipe,
    ProductModalComponent,
    StockEntryModalComponent,
    StockAdjustModalComponent,
  ],
  templateUrl: './product-detail.component.html',
})
export class ProductDetailComponent {
  readonly id = input.required<string>();

  private readonly products = inject(ProductService);
  private readonly stocks = inject(ProductStockService);
  private readonly movementsApi = inject(StockMovementService);
  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);

  protected readonly product = signal<Product | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly movements = signal<StockMovement[]>([]);
  protected readonly movementsMeta = signal<PaginatedMeta | null>(null);
  protected readonly movementsLoading = signal(false);
  protected readonly movementsPage = signal(1);

  protected readonly editOpen = signal(false);
  protected readonly submittingEdit = signal(false);
  protected readonly entryOpen = signal(false);
  protected readonly submittingEntry = signal(false);
  protected readonly adjustOpen = signal(false);
  protected readonly submittingAdjust = signal(false);
  protected readonly deleting = signal(false);

  protected readonly canEdit = computed(() => this.auth.hasPermission('products.update'));
  protected readonly canDelete = computed(() => this.auth.hasPermission('products.delete'));
  protected readonly canViewMovements = computed(() => this.auth.hasPermission('stock_movements.view'));
  protected readonly canCreateMovement = computed(() => this.auth.hasPermission('stock_movements.create'));
  protected readonly canAdjust = computed(() => this.auth.hasPermission('product_stocks.adjust'));

  protected readonly belowMinimum = computed(() => {
    const product = this.product();
    if (product?.stock === undefined) return false;
    return Number(product.stock.current_quantity) < Number(product.minimum_stock);
  });

  constructor() {
    effect(() => {
      const productId = Number(this.id());
      if (!Number.isInteger(productId) || productId <= 0) {
        this.errorMessage.set('El identificador del producto no es válido.');
        return;
      }
      void this.load(productId);
    });
  }

  protected openEditModal(): void {
    this.editOpen.set(true);
  }

  protected closeEditModal(): void {
    if (this.submittingEdit()) return;
    this.editOpen.set(false);
  }

  protected openEntryModal(): void {
    this.entryOpen.set(true);
  }

  protected closeEntryModal(): void {
    if (this.submittingEntry()) return;
    this.entryOpen.set(false);
  }

  protected openAdjustModal(): void {
    this.adjustOpen.set(true);
  }

  protected closeAdjustModal(): void {
    if (this.submittingAdjust()) return;
    this.adjustOpen.set(false);
  }

  protected goToMovementsPage(page: number): void {
    const meta = this.movementsMeta();
    if (meta === null) return;
    if (page < 1 || page > meta.last_page) return;
    this.movementsPage.set(page);
    void this.loadMovements();
  }

  async submitEdit(value: ProductFormValue): Promise<void> {
    const product = this.product();
    if (product === null) return;

    this.submittingEdit.set(true);

    const payload: ProductData = {
      name: value.name,
      description: value.description,
      sale_price: value.sale_price,
      cost_price: value.cost_price,
      doses_per_package: value.doses_per_package,
      minimum_stock: value.minimum_stock,
      is_sellable: value.is_sellable,
      is_active: value.is_active,
    };

    try {
      const updated = await this.products.update(product.id, payload);
      this.product.set({ ...updated, stock: product.stock });
      this.editOpen.set(false);
      this.notifications.toast.success('Producto actualizado.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingEdit.set(false);
    }
  }

  async deleteProduct(): Promise<void> {
    const product = this.product();
    if (product === null || this.deleting()) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar este producto?',
      message: `El producto "${product.name}" se eliminará del catálogo. Si tiene historial de stock o ventas no se podrá borrar.`,
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.deleting.set(true);
    try {
      await this.products.delete(product.id);
      this.notifications.toast.success('Producto eliminado.');
      await this.router.navigate(['/panel/productos']);
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? 'No se ha podido eliminar el producto.';
      this.notifications.toast.error(message);
    } finally {
      this.deleting.set(false);
    }
  }

  async submitEntry(value: StockEntryFormValue): Promise<void> {
    const product = this.product();
    if (product === null) return;

    this.submittingEntry.set(true);

    const payload: StockEntryData = {
      product_id: product.id,
      movement_type_id: value.movement_type_id,
      package_quantity: value.package_quantity,
      reason: value.reason,
    };

    try {
      await this.movementsApi.create(payload);
      this.entryOpen.set(false);
      this.notifications.toast.success('Entrada de stock registrada.');
      await this.refreshAfterStockChange();
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingEntry.set(false);
    }
  }

  async submitAdjust(value: StockAdjustFormValue): Promise<void> {
    const product = this.product();
    if (product?.stock === undefined) return;

    this.submittingAdjust.set(true);

    try {
      await this.stocks.adjust(product.stock.id, value);
      this.adjustOpen.set(false);
      this.notifications.toast.success('Stock ajustado.');
      await this.refreshAfterStockChange();
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingAdjust.set(false);
    }
  }

  private async load(productId: number): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const product = await this.products.getById(productId);
      this.product.set(product);
      if (this.canViewMovements()) {
        this.movementsPage.set(1);
        await this.loadMovements();
      }
    } catch {
      const message = loadResourceError('el producto');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
    } finally {
      this.loading.set(false);
    }
  }

  private async loadMovements(): Promise<void> {
    const product = this.product();
    if (product === null || !this.canViewMovements()) return;

    this.movementsLoading.set(true);
    try {
      const result = await this.movementsApi.list({
        product_id: product.id,
        page: this.movementsPage(),
      });
      this.movements.set(result.data);
      this.movementsMeta.set(result.meta);
    } catch {
      this.notifications.toast.error(loadResourceError('los movimientos de stock'));
    } finally {
      this.movementsLoading.set(false);
    }
  }

  private async refreshAfterStockChange(): Promise<void> {
    const product = this.product();
    if (product === null) return;
    try {
      const refreshed = await this.products.getById(product.id);
      this.product.set(refreshed);
    } catch {
      this.notifications.toast.error(loadResourceError('el producto'));
    }
    if (this.canViewMovements()) {
      this.movementsPage.set(1);
      await this.loadMovements();
    }
  }
}
