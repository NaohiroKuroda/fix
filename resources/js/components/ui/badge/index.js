import { cva } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva(
    'inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 gap-1 [&>svg]:size-3 transition-colors',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary text-primary-foreground',
                secondary: 'border-transparent bg-secondary text-secondary-foreground',
                destructive: 'border-transparent bg-destructive text-destructive-foreground',
                outline: 'text-foreground',
                // soft / tinted（淡色バッジ。密なテーブルでも視認性が高くモダン）
                success: 'border-emerald-600/15 bg-emerald-50 text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-300',
                warning: 'border-amber-600/15 bg-amber-50 text-amber-700 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-300',
                muted: 'border-transparent bg-muted text-muted-foreground',
                info: 'border-blue-600/15 bg-blue-50 text-blue-700 dark:border-blue-400/20 dark:bg-blue-400/10 dark:text-blue-300',
                danger: 'border-red-600/15 bg-red-50 text-red-700 dark:border-red-400/20 dark:bg-red-400/10 dark:text-red-300',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);
