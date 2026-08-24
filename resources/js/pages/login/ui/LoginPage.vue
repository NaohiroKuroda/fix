<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { GuestLayout } from '@/shared/ui/layouts';
import { Card, CardHeader, CardTitle, CardContent } from '@/shared/ui/card';
import { Input } from '@/shared/ui/input';
import { Label } from '@/shared/ui/label';
import { Button } from '@/shared/ui/button';
import { Checkbox } from '@/shared/ui/checkbox';
import { Loader2 } from 'lucide-vue-next';

defineOptions({ layout: GuestLayout });

// Inertia のフォーム用リアクティブオブジェクト
const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    // バックエンドの AuthController@login へ POST 送信
    form.post('/login', {
        onFinish: () => {
            // パスワード入力欄だけ安全のためにクリア
            form.password = '';
        },
    });
};
</script>

<template>
    <Head title="ログイン" />

    <div class="w-full flex-1 flex items-center justify-center bg-muted/40 px-4">
        <Card class="w-full max-w-sm shadow-lg border bg-card text-card-foreground">
            <CardHeader class="space-y-1 text-center">
                <CardTitle class="text-2xl font-bold tracking-tight">ログイン</CardTitle>
            </CardHeader>

            <CardContent>
                <div
                    v-if="form.errors.username"
                    class="mb-4 rounded-md border border-destructive/50 bg-destructive/10 px-3 py-2 text-xs text-destructive"
                >
                    {{ form.errors.username }}
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="username">ユーザー名</Label>
                        <Input
                            id="username"
                            v-model="form.username"
                            type="text"
                            placeholder="username"
                            required
                            autocomplete="username"
                            class="bg-background"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">パスワード</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                            class="bg-background"
                        />
                    </div>

                    <div class="flex items-center space-x-2 pt-1">
                        <Checkbox id="remember" v-model="form.remember" />
                        <Label
                            for="remember"
                            class="text-sm font-medium leading-none cursor-pointer select-none"
                        >
                            ログイン状態を保持する
                        </Label>
                    </div>

                    <Button type="submit" class="mt-2 w-full" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        ログイン
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
