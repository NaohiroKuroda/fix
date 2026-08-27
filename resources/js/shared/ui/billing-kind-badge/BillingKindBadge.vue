<script setup lang="ts">
// 区分（もらい＝請求／払い＝支払）のラベル。見積依頼（PayableProjectCard）と
// 業者承諾確認（OrderProjectCard）で同じ見た目を使うため、1か所に切り出している。
// 片方だけ直して見た目がずれることを防ぐのが目的なので、配色・寸法はここでのみ定義する。
//
// 配色の考え方:
//   画面全体が FELIX ブランドの「紺（primary）＋金（#c4a35b）」の暖色系で、明細は白地。
//   淡い色を置くと沈むため、色相だけでなく「塗りの強さ」で差を付ける。
//     請求（もらい＝入る側／少数）… 青。行の地色と合わせて前に出す
//     支払（払い＝出る側／大多数）… グレー。背景へ引かせる
//   emerald（承認・回答あり）/ red（否認・回答なし）/ teal（業者追加）/ 金（primary）は
//   既に別の意味を持つため、区分には未使用の sky 系を割り当てて衝突を避ける。
//
// 形の考え方:
//   この要素はクリックできない。丸ピル・白抜き文字・影・枠線・本文と同じ字送りは
//   同じ行に並ぶボタン（見積送信／業者マイページ）の記号なので使わず、
//   角の立った小さめの「ラベル」に寄せて押せそうに見えないようにする。
withDefaults(defineProps<{
    /** 請求先（t_cost_quotations.is_billing_target）か。true=請求（もらい） / false=支払（払い）。 */
    billingTarget?: boolean;
}>(), {
    billingTarget: false,
});

const SHAPE = 'inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded px-3 py-1 text-sm font-bold tracking-wider';
</script>

<template>
    <span :class="[SHAPE, billingTarget ? 'bg-sky-600/15 text-sky-800' : 'bg-slate-500/10 text-slate-500']">
        {{ billingTarget ? '請求' : '支払' }}
    </span>
</template>
