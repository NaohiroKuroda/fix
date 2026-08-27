<script setup lang="ts">
// やり取り（コメント）モーダル。支払系（QuotationManagementScreen）と請求系（BillingScreen）で共用する。
//
// コメントは建物予算項目（t_building_budget_items）単位の1スレッドに集約されるため、
// 同じ項目なら支払・請求のどちらの画面から開いても同じ内容が見える。
// 呼び出し側は「どのエンドポイントを叩くか」だけを URL で渡す（支払＝quotation-messages /
// 請求＝billing-messages）。メッセージの取得・投稿・添付の状態は本コンポーネントが持つ。
import { computed, nextTick, ref, watch } from 'vue';
import { X, MessageSquare, Send, Paperclip, FileText } from 'lucide-vue-next';
import type { QuotationChatMessage } from '@/shared/api';

const props = defineProps<{
    /** モーダルの表示状態。 */
    open: boolean;
    /** 一覧取得の URL（GET）。null の間は取得しない。 */
    indexUrl: string | null;
    /** 投稿の URL（POST）。null の間は送信できない。 */
    storeUrl: string | null;
    /** 見出しに出す項目名。 */
    itemName?: string | null;
    /** 見出しに出す案件（実行予算）名。 */
    buildingName?: string | null;
    /** 自分の役割。フッターの案内文に出す。 */
    myRole?: 'manager' | 'staff';
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    /** 投稿が成功したとき。呼び出し側は一覧のコメント件数バッジを更新する。 */
    (e: 'posted'): void;
}>();

const messages = ref<QuotationChatMessage[]>([]);
const body = ref('');
const loading = ref(false);
const sending = ref(false);
const error = ref<string | null>(null);
const scrollArea = ref<HTMLElement | null>(null);

// 添付ファイル（送信前の保留リスト）。ドラッグ&ドロップ／ファイル選択で追加する。
const files = ref<File[]>([]);
const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const openFilePicker = (): void => fileInput.value?.click();
// 添付の制約（06_添付ファイル_詳細設計 §1・§2）。サーバ側 StoreQuotationMessageRequest と一致させる。
const ALLOWED_EXTENSIONS = [
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif',
    'pdf', 'txt',
    'xlsx', 'xls', 'docx', 'doc', 'pptx', 'ppt', 'csv',
    'dwg', 'dxf', 'jww', 'jwc', 'sfc', 'p21',
    'zip',
];
const ACCEPT = ALLOWED_EXTENSIONS.map((e) => `.${e}`).join(',');
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const MAX_FILES = 5;

// アップロード前チェック：拡張子ホワイトリスト外・10MB超・5件超をこの場で弾いてメッセージ表示する。
const addFiles = (list: FileList | null): void => {
    if (!list || list.length === 0) {
        return;
    }

    const rejected: string[] = [];
    const accepted: File[] = [];
    for (const file of Array.from(list)) {
        const ext = file.name.includes('.') ? (file.name.split('.').pop() ?? '').toLowerCase() : '';
        if (!ALLOWED_EXTENSIONS.includes(ext)) {
            rejected.push(`${file.name}（非対応の形式）`);
            continue;
        }
        if (file.size > MAX_FILE_SIZE) {
            rejected.push(`${file.name}（10MB超）`);
            continue;
        }
        accepted.push(file);
    }

    const room = Math.max(0, MAX_FILES - files.value.length);
    const toAdd = accepted.slice(0, room);
    if (toAdd.length > 0) {
        files.value = [...files.value, ...toAdd];
    }

    const errors: string[] = [];
    if (rejected.length > 0) {
        errors.push(`次のファイルは添付できません: ${rejected.join('、')}`);
    }
    if (accepted.length > toAdd.length) {
        errors.push(`添付は${MAX_FILES}件までです。`);
    }
    if (errors.length > 0) {
        error.value = errors.join(' ');
    }
};
const onFileInputChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    addFiles(input.files);
    input.value = ''; // 同じファイルを続けて選べるようクリア。
};
const removeFile = (index: number): void => {
    files.value = files.value.filter((_, i) => i !== index);
};
const onDrop = (event: DragEvent): void => {
    dragOver.value = false;
    addFiles(event.dataTransfer?.files ?? null);
};
// 送信可否：本文か添付のいずれかがあり、送信中でないこと。
const canSend = computed(() => (body.value.trim().length > 0 || files.value.length > 0) && !sending.value);
const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

// CSRF（axios 未導入のため XSRF-TOKEN クッキーを fetch ヘッダへ手当てする）。
const xsrfToken = (): string =>
    decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');

const scrollToBottom = (): void => {
    void nextTick(() => {
        if (scrollArea.value) {
            scrollArea.value.scrollTop = scrollArea.value.scrollHeight;
        }
    });
};

const fetchMessages = async (): Promise<void> => {
    if (props.indexUrl === null) {
        return;
    }
    loading.value = true;
    try {
        const res = await fetch(props.indexUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        messages.value = data.messages ?? [];
        scrollToBottom();
    } catch {
        error.value = 'メッセージの取得に失敗しました。';
    } finally {
        loading.value = false;
    }
};

const send = async (): Promise<void> => {
    const text = body.value.trim();
    // 本文・添付いずれか必須。
    if (props.storeUrl === null || sending.value || (!text && files.value.length === 0)) {
        return;
    }
    sending.value = true;
    error.value = null;
    try {
        // 添付を含めるため multipart/form-data で送る（Content-Type はブラウザに任せる）。
        const form = new FormData();
        form.append('body', text);
        files.value.forEach((file) => form.append('files[]', file));
        const res = await fetch(props.storeUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
            credentials: 'same-origin',
            body: form,
        });
        if (!res.ok) {
            throw new Error('failed');
        }
        const data = await res.json();
        if (data.message) {
            messages.value.push(data.message);
        }
        body.value = '';
        files.value = [];
        emit('posted');
        scrollToBottom();
    } catch {
        error.value = '送信に失敗しました。';
    } finally {
        sending.value = false;
    }
};

// 開いた（または対象が切り替わった）タイミングで読み直す。閉じたら保留中の入力を捨てる。
watch(
    () => [props.open, props.indexUrl] as const,
    ([isOpen]) => {
        if (!isOpen) {
            files.value = [];
            dragOver.value = false;
            return;
        }
        messages.value = [];
        body.value = '';
        files.value = [];
        error.value = null;
        void fetchMessages();
    },
    { immediate: true },
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4"
            @click.self="emit('close')"
        >
            <div
                class="relative flex h-[80vh] w-full max-w-lg flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
                @dragenter.prevent="dragOver = true"
                @dragover.prevent="dragOver = true"
                @dragleave.prevent="dragOver = false"
                @drop.prevent="onDrop"
            >
                <!-- ドラッグ中のドロップ案内オーバーレイ -->
                <div
                    v-if="dragOver"
                    class="pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#c4a35b] bg-[#c4a35b]/10 backdrop-blur-sm"
                >
                    <Paperclip class="size-8 text-[#c4a35b]" />
                    <p class="text-sm font-semibold text-[#8a6a25]">ここにファイルをドロップして添付</p>
                </div>
                <!-- ヘッダー：対象の案件・項目 -->
                <div class="flex items-start gap-2 border-b px-4 py-3">
                    <MessageSquare class="mt-0.5 size-5 text-[#c4a35b]" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">コメント履歴</p>
                        <p v-if="buildingName" class="truncate text-[11px] text-slate-400">{{ buildingName }}</p>
                        <p class="truncate text-xs text-slate-500">{{ itemName || '項目' }}</p>
                    </div>
                    <button
                        type="button"
                        class="flex size-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        title="閉じる"
                        @click="emit('close')"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <!-- コメント履歴一覧（LINE風：自分の投稿は右寄せ、相手は左寄せ） -->
                <div ref="scrollArea" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-4 py-4">
                    <p v-if="loading" class="py-8 text-center text-sm text-slate-400">読み込み中…</p>
                    <p v-else-if="!messages.length" class="py-8 text-center text-sm text-slate-400">
                        まだコメントはありません。最初のコメントを残しましょう。
                    </p>
                    <div v-for="m in messages" :key="m.id" class="flex" :class="m.isMine ? 'justify-end' : 'justify-start'">
                        <div class="flex max-w-[80%] flex-col" :class="m.isMine ? 'items-end' : 'items-start'">
                            <!-- 送信者情報（名前）。自分は右寄せに並べる。 -->
                            <div
                                class="mb-1 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500"
                                :class="m.isMine ? 'flex-row-reverse' : ''"
                            >
                                <span class="font-semibold text-slate-700">{{ m.senderName }}</span>
                                <span v-if="m.isMine" class="rounded bg-slate-100 px-1 py-0.5 leading-none text-slate-400">自分</span>
                            </div>
                            <!-- 吹き出し：自分＝ゴールド（右）、相手＝白（左）。 -->
                            <div
                                class="rounded-2xl px-3 py-2 shadow-sm"
                                :class="m.isMine
                                    ? 'rounded-br-sm border border-[#c4a35b] bg-[#c4a35b] text-white'
                                    : 'rounded-bl-sm border border-slate-200 bg-white text-slate-800'"
                            >
                                <div v-if="m.body" class="whitespace-pre-wrap break-words text-sm">{{ m.body }}</div>
                                <!-- 添付ファイル：サムネのある画像はサムネ表示、その他（HEIC・文書等）はファイル名リンク。クリックは常にダウンロード。 -->
                                <div v-if="m.files.length" class="mt-2 flex flex-wrap gap-2" :class="m.isMine ? 'justify-end' : ''">
                                    <template v-for="f in m.files" :key="f.id">
                                        <a
                                            v-if="f.thumbUrl"
                                            :href="f.downloadUrl"
                                            :download="f.name"
                                            :title="`${f.name} をダウンロード`"
                                            class="block overflow-hidden rounded-lg border border-slate-200 transition hover:border-[#c4a35b]"
                                        >
                                            <img :src="f.thumbUrl" :alt="f.name" class="size-24 object-cover" />
                                        </a>
                                        <a
                                            v-else
                                            :href="f.downloadUrl"
                                            :download="f.name"
                                            :title="`${f.name} をダウンロード`"
                                            class="inline-flex max-w-[220px] items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs text-slate-700 transition hover:border-[#c4a35b] hover:bg-[#c4a35b]/10"
                                        >
                                            <FileText class="size-4 shrink-0 text-[#c4a35b]" />
                                            <span class="truncate">{{ f.name }}</span>
                                            <span class="shrink-0 text-slate-400">{{ formatFileSize(f.size) }}</span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                            <!-- 送信時刻 -->
                            <span class="mt-1 text-[10px] text-slate-400">{{ m.createdAt }}</span>
                        </div>
                    </div>
                </div>

                <!-- 入力 -->
                <div class="border-t px-3 py-3">
                    <p v-if="error" class="mb-1 text-xs text-red-600">{{ error }}</p>
                    <!-- 添付予定ファイルの一覧（送信前・個別に取り消し可）。 -->
                    <div v-if="files.length" class="mb-2 flex flex-wrap gap-1.5">
                        <span
                            v-for="(f, i) in files"
                            :key="`${f.name}-${i}`"
                            class="inline-flex max-w-[220px] items-center gap-1.5 rounded-lg border border-[#c4a35b]/40 bg-[#c4a35b]/10 px-2 py-1 text-xs text-[#8a6a25]"
                        >
                            <FileText class="size-3.5 shrink-0" />
                            <span class="truncate">{{ f.name }}</span>
                            <span class="shrink-0 text-[#8a6a25]/60">{{ formatFileSize(f.size) }}</span>
                            <button
                                type="button"
                                class="shrink-0 rounded p-0.5 text-[#8a6a25]/70 transition hover:bg-[#c4a35b]/20 hover:text-[#8a6a25]"
                                title="添付を取り消す"
                                @click="removeFile(i)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </span>
                    </div>
                    <div class="flex items-end gap-2">
                        <!-- ファイル選択（端末内のファイルを開く）。実体は隠し input。 -->
                        <input ref="fileInput" type="file" multiple class="hidden" :accept="ACCEPT" @change="onFileInputChange" />
                        <button
                            type="button"
                            class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-500 transition hover:border-[#c4a35b] hover:bg-[#c4a35b]/10 hover:text-[#8a6a25]"
                            title="ファイルを添付"
                            @click="openFilePicker"
                        >
                            <Paperclip class="size-5" />
                        </button>
                        <textarea
                            v-model="body"
                            rows="2"
                            class="flex-1 resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#c4a35b] focus:outline-none focus:ring-2 focus:ring-[#c4a35b]/30"
                            placeholder="コメントを入力（Enterで改行）"
                            @keydown.enter.exact.prevent="send"
                        />
                        <button
                            type="button"
                            class="flex h-10 shrink-0 items-center gap-1.5 rounded-lg px-3 text-sm font-semibold text-white transition"
                            :class="canSend ? 'bg-[#c4a35b] hover:bg-[#b3923f]' : 'cursor-not-allowed bg-[#c4a35b]/40'"
                            :disabled="!canSend"
                            @click="send"
                        >
                            <Send class="size-4" />{{ sending ? '送信中' : 'コメントする' }}
                        </button>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">
                        あなたは「{{ myRole === 'manager' ? '部長' : '部下' }}」としてコメントします。ファイルはドラッグ&ドロップでも添付できます。
                    </p>
                </div>
            </div>
        </div>
    </Teleport>
</template>
