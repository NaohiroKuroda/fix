// やり取り（コメント）のサーバ表現。QuotationMessageController と一致させること。
// 見積管理・発注納品の両フローが同じエンドポイント（shared/api/routes/quotation-management/
// quotation-messages）を使うため、DTO は shared/api に置く。

/** やり取り（コメント履歴）の添付ファイル1件。 */
export interface QuotationChatFile {
    id: number;
    name: string;
    mime: string | null;
    size: number;
    /** 画像（サムネ生成対象）か。 */
    isImage: boolean;
    /** 画像サムネイルのインライン配信URL。画像かつサムネ生成済みのみ。無ければ null。 */
    thumbUrl: string | null;
    /** クリック時の端末ダウンロード用URL（Content-Disposition: attachment）。全ファイル共通のクリック先。 */
    downloadUrl: string;
}

/** やり取り（チャット）の1発言。 */
export interface QuotationChatMessage {
    id: number;
    /** 発言者の役割：manager=建設部部長 / staff=部下。 */
    senderRole: 'manager' | 'staff';
    /** 自分（ログイン中ユーザー）の投稿か。true=右寄せ、false=左寄せで表示する。 */
    isMine: boolean;
    senderName: string;
    body: string;
    createdAt: string | null;
    /** 添付ファイル（無ければ空配列）。 */
    files: QuotationChatFile[];
}
