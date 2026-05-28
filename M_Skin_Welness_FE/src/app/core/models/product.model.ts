export interface ProductStockInfo {
  id: number;
  current_quantity: string;
}

export interface Product {
  id: number;
  center_id: number;
  name: string;
  description: string | null;
  sale_price: string | null;
  cost_price: string | null;
  doses_per_package: number;
  minimum_stock: string;
  is_sellable: boolean;
  is_active: boolean;
  stock?: ProductStockInfo;
  created_at: string | null;
  updated_at: string | null;
}
