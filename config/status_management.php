<?php

/*
 * ステータス管理画面の表示・検索に使うラベル定義。
 * felix_total（旧 laravel-admin）の config/constant.php 由来の値を、
 * 本画面で扱う範囲に絞って再定義したもの。
 */

return [
    // 1ページあたりの案件数（ページネーション）
    'per_page' => 10,

    // 実行予算一覧の並べ替え選択肢
    'budget_sort' => [
        'id_desc' => '新着順',
        'handover_asc' => '引渡日が近い順',
    ],

    // 選定承認（建設部/設計部/常務）の状態ラベル: tmp_status / design_status / fix_status
    // 0=未承認/未選定, 1=承認済/選定済, 2=否認済
    'select_state' => [
        0 => '未承認',
        1 => '承認済',
        2 => '否認済',
    ],

    // 完了報告（第一/第二/第三承認）の状態ラベル
    'approval_state' => [
        'none' => '',
        'pending' => '未承認',
        'approved' => '承認済',
        'denied' => '否認済',
    ],

    // 依頼（相見積送信）の状態ラベル
    'request_state' => [
        'sent' => '送信済',
        'notified' => '通知済',
        'none' => '未送信',
    ],

    // 区分（estimate_units.sub_cate）
    'sub_cate' => [
        0 => '販売外',
        1 => '仕入',
        2 => '建設',
        3 => '販売',
        4 => '金融',
        5 => '融資',
        99 => 'その他',
    ],

    // 選定承認ステータス（estimate_units.company_select_status）
    'company_select_status' => [
        0 => '未選定',
        1 => '建設部選定待ち',
        2 => '設計部承認待ち',
        3 => '常務承認待ち',
        4 => '選定承認済み',
    ],

    // 完了報告（承認待ち）ステータス（estimate_units.complete_status）
    'complete_status' => [
        0 => '未着手',
        1 => '建設部承認待ち',
        2 => '設計部承認待ち',
        3 => '常務承認待ち',
        4 => '承認済み',
    ],
];
