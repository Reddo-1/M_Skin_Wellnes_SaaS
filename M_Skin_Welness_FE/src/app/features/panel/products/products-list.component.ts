import { CurrencyPipe, DecimalPipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { ProductService, ProductData } from '../../../core/services/product.service';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Product } from '../../../core/models/product.model';
import { PaginatedMeta } from '../../../core/models/paginated.model';
import { GENERIC_ERROR, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../shared/ui/segmented-control/segmented-control.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';
import { ProductModalComponent, ProductFormValue } from './modals/product-modal/product-modal.component';
import { TableLoadingOverlayComponent } from '../../../shared/ui/table-loading-overlay/table-loading-overlay.component';
import { SearchInputComponent } from '../../../shared/ui/search-input/search-input.component';

type ActiveFilter = 'all' | 'active' | 'inactive';

const ACTIVE_FILTER_OPTIONS: SegmentedControlOption<ActiveFilter>[] = [
  { value: 'all', label: 'Todos' },
  { value: 'active', label: 'Activos' },
  { value: 'inactive', label: 'Inactivos' },
];

@Component({
  selector: 'app-products-list',
  standalone: true,
  imports: [
    CurrencyPipe,
    DecimalPipe,
    RouterLink,
    AlertComponent,
    SegmentedControlComponent,
    TableScrollHintComponent,
    ProductModalComponent,
    TableLoadingOverlayComponent,
    SearchInputComponent,
  ],
  templateUrl: './products-list.component.html',
})
export class ProductsListComponent {
  private readonly products = inject(ProductService);
  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);

  protected readonly items = signal<Product[]>([]);
  protected readonly meta = signal<PaginatedMeta | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly searchInput = signal('');
  protected readonly activeFilter = signal<ActiveFilter>('active');
  protected readonly page = signal(1);
  protected readonly activeFilterOptions = ACTIVE_FILTER_OPTIONS;

  protected readonly modalOpen = signal(false);
  protected readonly editingProduct = signal<Product | null>(null);
  protected readonly submitting = signal(false);

  protected readonly busyRowIds = signal<Set<number>>(new Set());

  protected readonly showcase = computed(() => this.auth.effectiveRoles().includes('cliente'));

  constructor() {
    void this.load();
  }

  protected isRowBusy(id: number): boolean {
    return this.busyRowIds().has(id);
  }

  protected belowMinimum(product: Product): boolean {
    if (product.stock === undefined) return false;
    return Number(product.stock.current_quantity) < Number(product.minimum_stock);
  }

  protected onSearch(value: string): void {
    this.searchInput.set(value);
    this.page.set(1);
    void this.load();
  }

  protected onActiveFilterChange(value: ActiveFilter): void {
    this.activeFilter.set(value);
    this.page.set(1);
    void this.load();
  }

  protected goToPage(page: number): void {
    const meta = this.meta();
    if (meta === null) return;
    if (page < 1 || page > meta.last_page) return;
    this.page.set(page);
    void this.load();
  }

  protected openCreateModal(): void {
    this.editingProduct.set(null);
    this.modalOpen.set(true);
  }

  protected openEditModal(product: Product): void {
    this.editingProduct.set(product);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  async submitProduct(value: ProductFormValue): Promise<void> {
    this.submitting.set(true);

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

    const editing = this.editingProduct();

    try {
      if (editing !== null) {
        await this.products.update(editing.id, payload);
        this.notifications.toast.success('Producto actualizado.');
        await this.load();
      } else {
        await this.products.create(payload);
        this.notifications.toast.success('Producto creado.');
        this.page.set(1);
        await this.load();
      }
      this.modalOpen.set(false);
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  async toggleActive(product: Product): Promise<void> {
    if (this.isRowBusy(product.id)) return;

    const action = product.is_active ? 'desactivar' : 'reactivar';
    const confirmed = await this.notifications.modal.confirm({
      variant: product.is_active ? 'warning' : 'info',
      title: product.is_active ? '¿Desactivar este producto?' : '¿Reactivar este producto?',
      message: product.is_active
        ? `El producto "${product.name}" dejará de aparecer en los listados activos y no se podrá vender. Su historial se conserva.`
        : `El producto "${product.name}" volverá a estar disponible en el catálogo del centro.`,
      confirmText: product.is_active ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(product.id, true);
    try {
      const updated = await this.products.setActive(product.id, !product.is_active);
      this.replaceItem(updated);
      this.notifications.toast.success(
        product.is_active ? 'Producto desactivado.' : 'Producto reactivado.',
      );
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? `No se ha podido ${action} el producto. Vuelve a intentarlo en unos segundos.`;
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(product.id, false);
    }
  }

  async deleteProduct(product: Product): Promise<void> {
    if (this.isRowBusy(product.id)) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar este producto?',
      message: `El producto "${product.name}" se eliminará del catálogo. Si tiene historial de stock o ventas no se podrá borrar.`,
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(product.id, true);
    try {
      await this.products.delete(product.id);
      this.items.update((items) => items.filter((item) => item.id !== product.id));
      this.notifications.toast.success('Producto eliminado.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? 'No se ha podido eliminar el producto.';
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(product.id, false);
    }
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    const showcase = this.showcase();
    try {
      const result = await this.products.list({
        search: this.searchInput(),
        is_active: showcase ? true : this.activeFilterValue(),
        is_sellable: showcase ? true : undefined,
        page: this.page(),
      });
      this.items.set(result.data);
      this.meta.set(result.meta);
    } catch {
      const message = loadResourceError('los productos');
      this.errorMessage.set(message);
    } finally {
      this.loading.set(false);
    }
  }

  private activeFilterValue(): boolean | undefined {
    const value = this.activeFilter();
    if (value === 'active') return true;
    if (value === 'inactive') return false;
    return undefined;
  }

  private replaceItem(updated: Product): void {
    this.items.update((items) => items.map((item) => (item.id === updated.id ? updated : item)));
  }

  private markBusy(id: number, busy: boolean): void {
    this.busyRowIds.update((current) => {
      const next = new Set(current);
      if (busy) {
        next.add(id);
      } else {
        next.delete(id);
      }
      return next;
    });
  }
}
