// shared/api セグメントの public API。
// サーバから受け取る共通 DTO はここから import する（内部ファイルへの直接 import は禁止）。
//
// 例外: Wayfinder 自動生成物（actions/ routes/ wayfinder/）はここへ集約せず、
// 生成パスを直接 import する（自動生成のため手で維持できない。frontend.md 4.3.5-4）。
export type { Pagination } from './pagination';
export type { ProjectFilters } from './project-filter';
export type { QuotationChatFile, QuotationChatMessage } from './quotation-message';
