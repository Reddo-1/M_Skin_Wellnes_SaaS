export interface StockMovementRef {
  id: number;
  name: string;
}

export interface StockMovement {
  id: number;
  center_id: number;
  product_id: number;
  movement_type_id: number;
  quantity: string;
  previous_quantity: string;
  new_quantity: string;
  reference_type: string | null;
  reference_id: number | null;
  user_id: number | null;
  reason: string | null;
  product?: StockMovementRef;
  type?: StockMovementRef;
  user?: StockMovementRef;
  created_at: string | null;
}
