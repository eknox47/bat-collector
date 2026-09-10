export interface Book {
  id: string,
  isbn: string;
  name: string,
  title: string;
  author: string | null;
  publisher: string | null;
  published_date: string | null;
  page_count: number | null;
  cover_url: string | null;
}

export interface BookDetails extends Book {
  description: string | null;
  categories: string[];
  language: string | null;
  average_rating: number | null;
}