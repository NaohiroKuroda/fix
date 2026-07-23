<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used; however, you remain free to modify this option if you wish.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => 'bcrypt',

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Bcrypt algorithm. This will allow you
    | to control the amount of time it takes to hash the given password.
    |
    | verify: felix_total と共有する admin_users には、移行前のレガシー由来で
    |   bcrypt 以外のパスワード（空文字・MD5 相当の32文字）が混在している。
    |   verify=true（フレームワーク既定）だと、これらのハッシュを照合した瞬間に
    |   BcryptHasher が RuntimeException を投げ、ログインが 500 になってしまう。
    |   非 bcrypt ハッシュは新システムでは認証させない（＝照合失敗で弾く）方針のため、
    |   verify=false にして例外ではなく通常の「認証失敗」に倒す。password_verify() は
    |   空文字・非 bcrypt を安全に false で返すため、正しく「ユーザー名またはパスワードが
    |   正しくありません。」へ流れる。
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => false,
        'limit' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Argon algorithm. These will allow you
    | to control the amount of time it takes to hash the given password.
    |
    */

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | Setting this option to true will tell Laravel to automatically rehash
    | the user's password during login if the configured work factor for
    | the algorithm has changed, allowing for a smoother user experience.
    |
    */

    'rehash_on_login' => true,

];
