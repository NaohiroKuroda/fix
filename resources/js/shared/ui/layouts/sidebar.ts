import type { InjectionKey, Ref } from 'vue';

/**
 * サイドバーが折りたたまれているか。AppLayout が provide し、
 * 画面側（features / pages）が inject して固定ヘッダーの左余白を調整する。
 * 文字列キーだと型が付かず綴り違いにも気付けないため、キーをここで公開する。
 */
export const SIDEBAR_COLLAPSED: InjectionKey<Ref<boolean>> = Symbol('sidebarCollapsed');
