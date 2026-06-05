export type CenterFileType = 'logo' | 'header' | 'default_avatar';

export interface CenterFile {
  id: number;
  center_id: number;
  type: CenterFileType;
  url: string;
  mime_type: string | null;
  created_at: string | null;
}
