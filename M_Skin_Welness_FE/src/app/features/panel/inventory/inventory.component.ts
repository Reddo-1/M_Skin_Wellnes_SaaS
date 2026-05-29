import { Component, computed, inject, signal } from '@angular/core';
import { DatePipe, DecimalPipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ProductStockService } from '../../../core/services/product-stock.service';
import { StockMovementService, StockEntryData } from '../../../core/services/stock-movement.service';
import { LookupService } from '../../../core/services/lookup.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ProductStock } from '../../../core/models/product-stock.model';
import { StockMovement } from '../../../core/models/stock-movement.model';
import { PaginatedMeta } from '../../../core/models/paginated.model';
import { GENERIC_ERROR, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../shared/ui/segmented-control/segmented-control.component';
import { SelectComponent, SelectOption } from '../../../shared/ui/select/select.component';
import { DatePickerComponent } from '../../../shared/ui/date-picker/date-picker.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';
import { InventoryEntryModalComponent, InventoryEntryFormValue } from './modals/inventory-entry-modal/inventory-entry-modal.component';

type InventoryTab = 'stock' | 'movimientos';
type StockFilter = 'all' | 'below';

const TAB_OPTIONS: SegmentedControlOption<InventoryTab>[] = [
  { value: 'stock', label: 'Stock actual' },
  { value: 'movimientos', label: 'Movimientos' },
];

const STOCK_FILTER_OPTIONS: SegmentedControlOption<StockFilter>[] = [
  { value: 'all', label: 'Todos' },
  { value: 'below', label: 'Bajo mínimo' },
];

const MOVEMENT_TYPE_LABELS: Record<string, string> = {
  entrada: 'Entrada',
  salida_venta: 'Salida por venta',
  uso_sesion: 'Uso en sesión',
  ajuste_manual: 'Ajuste manual',
  devolucion: 'Devolución',
};

interface EntryTarget {
  id: number;
  name: string;
}

@Component({
  selector: 'app-inventory',
  standalone: true,
  imports: [
    DatePipe,
    DecimalPipe,
    AlertComponent,
    SegmentedControlComponent,
    SelectComponent,
    DatePickerComponent,
    TableScrollHintComponent,
    InventoryEntryModalComponent,
  ],
  templateUrl: './inventory.component.html',
})
export class InventoryComponent {
  private readonly productStocks = inject(ProductStockService);
  private readonly stockMovements = inject(StockMovementService);
  private readonly lookups = inject(LookupService);
  private readonly notifications = inject(NotificationService);

  protected readonly tab = signal<InventoryTab>('stock');
  protected readonly tabOptions = TAB_OPTIONS;
  protected readonly stockFilterOptions = STOCK_FILTER_OPTIONS;

  protected readonly stockItems = signal<ProductStock[]>([]);
  protected readonly stockMeta = signal<PaginatedMeta | null>(null);
  protected readonly stockPage = signal(1);
  protected readonly stockFilter = signal<StockFilter>('all');

  protected readonly movementItems = signal<StockMovement[]>([]);
  protected readonly movementsMeta = signal<PaginatedMeta | null>(null);
  protected readonly movementsPage = signal(1);
  protected readonly typeFilter = signal('');
  protected readonly fromDate = signal('');
  protected readonly toDate = signal('');

  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly entryTarget = signal<EntryTarget | null>(null);
  protected readonly submittingEntry = signal(false);

  protected readonly typeOptions = computed<SelectOption[]>(() => [
    { value: '', label: 'Todos los tipos' },
    ...this.lookups.stockMovementTypes().map((type) => ({
      value: String(type.id),
      label: MOVEMENT_TYPE_LABELS[type.name] ?? type.name,
    })),
  ]);

  constructor() {
    void this.loadStock();
  }

  protected onTabChange(value: InventoryTab): void {
    this.tab.set(value);
    if (value === 'stock') {
      void this.loadStock();
    } else {
      void this.loadMovements();
    }
  }

  protected onStockFilterChange(value: StockFilter): void {
    this.stockFilter.set(value);
    this.stockPage.set(1);
    void this.loadStock();
  }

  protected onTypeFilterChange(value: string): void {
    this.typeFilter.set(value);
    this.movementsPage.set(1);
    void this.loadMovements();
  }

  protected onFromChange(value: string | null): void {
    this.fromDate.set(value ?? '');
    this.movementsPage.set(1);
    void this.loadMovements();
  }

  protected onToChange(value: string | null): void {
    this.toDate.set(value ?? '');
    this.movementsPage.set(1);
    void this.loadMovements();
  }

  protected goToStockPage(page: number): void {
    const meta = this.stockMeta();
    if (meta === null || page < 1 || page > meta.last_page) return;
    this.stockPage.set(page);
    void this.loadStock();
  }

  protected goToMovementsPage(page: number): void {
    const meta = this.movementsMeta();
    if (meta === null || page < 1 || page > meta.last_page) return;
    this.movementsPage.set(page);
    void this.loadMovements();
  }

  protected typeLabel(name: string | undefined): string {
    if (name === undefined) return '—';
    return MOVEMENT_TYPE_LABELS[name] ?? name;
  }

  protected belowMinimum(stock: ProductStock): boolean {
    if (stock.product === undefined) return false;
    return Number(stock.current_quantity) < Number(stock.product.minimum_stock);
  }

  protected openEntryModal(stock: ProductStock): void {
    if (stock.product === undefined) return;
    this.entryTarget.set({ id: stock.product.id, name: stock.product.name });
  }

  protected closeEntryModal(): void {
    if (this.submittingEntry()) return;
    this.entryTarget.set(null);
  }

  async submitEntry(value: InventoryEntryFormValue): Promise<void> {
    const target = this.entryTarget();
    if (target === null) return;

    const payload: StockEntryData = {
      product_id: target.id,
      movement_type_id: value.movement_type_id,
      package_quantity: value.package_quantity,
      reason: value.reason,
    };

    this.submittingEntry.set(true);
    try {
      await this.stockMovements.create(payload);
      this.entryTarget.set(null);
      this.notifications.toast.success('Entrada de stock registrada.');
      await this.loadStock();
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingEntry.set(false);
    }
  }

  protected async loadStock(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const result = await this.productStocks.list({
        below_minimum: this.stockFilter() === 'below' ? true : undefined,
        page: this.stockPage(),
      });
      this.stockItems.set(result.data);
      this.stockMeta.set(result.meta);
    } catch {
      const message = loadResourceError('el stock');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
    } finally {
      this.loading.set(false);
    }
  }

  protected async loadMovements(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const result = await this.stockMovements.list({
        movement_type_id: this.typeFilter() === '' ? undefined : Number(this.typeFilter()),
        from: this.fromDate(),
        to: this.toDate(),
        page: this.movementsPage(),
      });
      this.movementItems.set(result.data);
      this.movementsMeta.set(result.meta);
    } catch {
      const message = loadResourceError('los movimientos de stock');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
    } finally {
      this.loading.set(false);
    }
  }
}
