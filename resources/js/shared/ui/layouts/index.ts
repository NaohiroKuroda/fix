// レイアウトの public API。
// アプリ全体で使うレイアウトだが、pages から参照する必要があるため app ではなく shared/ui に置く
// （app は pages の上位レイヤなので pages から import できない。frontend.md 4.3.6 参照）。
export { default as AppLayout } from './AppLayout.vue';
export { default as GuestLayout } from './GuestLayout.vue';
export { SIDEBAR_COLLAPSED } from './sidebar';
