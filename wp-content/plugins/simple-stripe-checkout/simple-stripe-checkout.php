<?php

/*
  Plugin Name: Simple Stripe Checkout
  Plugin URI: https://s-page.biz/plugins/simple-stripe-checkout/
  Description: 決済プラットフォーム「Stripe」の連携プラグイン
  Version: 1.0.4
  Author: growniche
  Author URI: https://www.growniche.co.jp/
*/

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

register_activation_hook(__FILE__, function() {

    // optionsテーブルに決済完了ページのSLUGが未登録の場合
    if (strlen(get_option(SimpleStripeCheckout::OPTION_KEY__CHECKEDOUT_PAGE_SLUG )) === 0) {
        // 決済完了の固定ページを作成
        $page_id = wp_insert_post(array(
            'post_name' => SimpleStripeCheckout::SLUG__CHECKEDOUT,
            'post_author' => 1,
            'post_title' => '決済完了',
            'post_content' => 'お支払いが完了しました。

ありがとうございます。
引き続きよろしくお願いいたします。',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
        $pages = get_pages('include=' . $page_id);
        if (count($pages) > 0) {
            // 決済完了ページのSLUGをoptionsテーブルに保存
            update_option(SimpleStripeCheckout::OPTION_KEY__CHECKEDOUT_PAGE_SLUG, $pages[0]->post_name);
        }
    }

    // optionsテーブルにキャンセル完了了ページのSLUGが未登録の場合
    if (strlen(get_option(SimpleStripeCheckout::OPTION_KEY__CANCEL_PAGE_SLUG)) === 0) {
        // キャンセル完了の固定ページを作成
        $page_id = wp_insert_post(array(
            'post_name' => SimpleStripeCheckout::SLUG__CANCEL,
            'post_author' => 1, 
            'post_title' => 'キャンセル完了',
            'post_content' => 'お支払いのキャンセルを承りました。

ありがとうございます。
またの機会がありましたらよろしくお願いいたします。',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
        $pages = get_pages('include=' . $page_id);
        if (count($pages) > 0) {
            // キャンセル完了ページのSLUGをoptionsテーブルに保存
            update_option(SimpleStripeCheckout::OPTION_KEY__CANCEL_PAGE_SLUG, $pages[0]->post_name);
        }
    }

    // optionsテーブルに決済確定完了ページのSLUGが未登録の場合
    if (strlen(get_option(SimpleStripeCheckout::OPTION_KEY__CAPTURE_COMPLETE_PAGE_SLUG)) === 0) {
        // 決済確定完了の固定ページを作成
        $page_id = wp_insert_post(array(
            'post_name' => SimpleStripeCheckout::SLUG__CAPTURE_COMPLETE,
            'post_author' => 1,
            'post_title' => '決済確定完了',
            'post_content' => '決済が確定し、お支払いが完了しました。

ありがとうございます。
引き続きよろしくお願いいたします。',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
        $pages = get_pages('include=' . $page_id);
        if (count($pages) > 0) {
            // 決済確定完了ページのSLUGをoptionsテーブルに保存
            update_option(SimpleStripeCheckout::OPTION_KEY__CAPTURE_COMPLETE_PAGE_SLUG, $pages[0]->post_name);
        }
    }

});

// WordPressの読み込みが完了してヘッダーが送信される前に実行するアクションに、
// SimpleStripeCheckoutクラスのインスタンスを生成するStatic関数をフック
add_action('init', 'SimpleStripeCheckout::instance');

/**
 * SimpleStripeCheckoutプラグインの商品情報モデルクラス
 */
class SimpleStripeCheckout_Product {

    /**
     * プロパティ：商品コード
     */
    public $code;

    /**
     * プロパティ：商品価格
     */
    public $price;

    /**
     * プロパティ：商品提供者名
     */
    public $provider_name;

    /**
     * プロパティ：商品名
     */
    public $name;

    /**
     * プロパティ：商品通貨
     */
    public $currency;

    /**
     * プロパティ：商品ボタン名
     */
    public $button_name;
}

/**
 * SimpleStripeCheckoutプラグインの商品情報一覧クラス
 */
class SimpleStripeCheckout_ProductList {

    private $id;
    private $items;
    private $type;

    public function getItems() {
        return $this->items;
    }

    public function getType() {
        return $this->type;
    }

    public function __construct($type, $items) {
        $this->type = $type;
        $this->items = $items;
    }

}

class SimpleStripeCheckout_ProductListTable extends WP_List_Table {

    private $product_list;
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        // 必ず親のコンストラクタを呼ぶ
        parent::__construct();
    }

    /**
     * 商品情報リストをセット
     */
    public function set_product_list($product_list) {
        $this->product_list = $product_list;
    }

    /**
     * 
     */
    public function prepare_items() {
        $info = new SimpleStripeCheckout_ProductList('商品一覧', $this->product_list);
        $this->items = $info->getItems();
        
        // 検索
        $s = isset($_REQUEST['s']) ? (string)$_REQUEST['s'] : '';
        if (!empty($s)) {
            $this->items = array_filter($this->items, function($item) use($s) {
                return
                    strpos($item->code, $s) ||
                    strpos($item->price, $s) ||
                    strpos($item->provider_name, $s) ||
                    strpos($item->name, $s) ||
                    strpos($item->currency, $s) ||
                    strpos($item->button_name, $s);
            });
        }
        
        // ソート関数
        $sort = function($a, $b, $bigA){
            if($a === $b) return 0;
            // $bigAが1なら昇順、-1なら降順
            return $a > $b ? $bigA : -$bigA;
        };

        $orderby  = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
        $order    = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : '';
        $orderDir = $order === 'asc' ? 1 : -1;

        $fnames = [
            'code'          => function($item){ return $item->code;          },
            'name'          => function($item){ return $item->name;          },
            'provider_name' => function($item){ return $item->provider_name; },
            'price'         => function($item){ return $item->price;         },
            'currency'      => function($item){ return $item->currency;      },
            'button_name'   => function($item){ return $item->button_name;   }
        ];

        $getter = isset($fnames[$orderby]) ? $fnames[$orderby] : null;

        if ($getter) {
            usort(
                $this->items,
                function($a, $b) use($getter, $sort, $orderDir) {
                    return $sort($getter($a), $getter($b), $orderDir);
                }
            );
        }
        
        // ページネーションを使う場合は設定
        $this->set_pagination_args([
            'total_items' => count($this->items),
            // 以下を設定しない場合は ceil(total_items / per_page) となる
            // 'total_pages' => 5, 
            'per_page' => 10
        ]);

        // ページ数を取得
        $pageLen = $this->get_pagination_arg('total_pages');

        // 現在のページ($_REQUEST['paged'])を取得、範囲を外れると修正される
        $paged = $this->get_pagenum();

        // 1ページあたりの件数
        $per_page = $this->get_pagination_arg('per_page');

        // ページネーションを独自に計算
        $this->items = array_slice(
            $this->items,
            $per_page * ($paged - 1),
            $per_page
        );
    }

    /**
     * 商品情報リストテーブルの左上のアクションリスト表示
     */
    protected function get_bulk_actions() {
        return [
            'delete' => '削除'
        ];
    }

    /**
     * 商品情報リストテーブルの上部中央に配置するその他のHTML
     */
    protected function extra_tablenav($witch) {
        echo "<div class=\"alignleft actions bulkactions\"></div>";
    }
    
    public function get_columns() {
        return [
            'cb' => 'チェックボックス',
            'name' => '商品名',
            'code' => '商品コード',
            'short_code' => 'ショートコード',
            'provider_name' => '提供者',
            'price' => '価格',
            'currency' => '通貨',
            'button_name' => 'ボタン名'
        ];
    }

    protected function column_cb($item) {
        $code = $item->code;
        return "<input type=\"checkbox\" name=\"checked[]\" value=\"{$code}\" />";
    }

    protected function column_default($item, $name) {
        switch($name) {
            // ここでcbが呼び出されることはない。
            // cbは特別にcolumn_cb($item)が呼び出される。
            case 'code':          return (string)$item->code;
            case 'short_code':    return '[' . SimpleStripeCheckout::PLUGIN_ID . ' code=' . (string)$item->code . ']';
            case 'name':          return esc_html($item->name);
            case 'provider_name': return esc_html($item->provider_name);
            case 'price':         return (string)$item->price;
            case 'currency':      return esc_html($item->currency);
            case 'button_name':   return esc_html($item->button_name);
        }
    }
    
    protected function column_name($item) {
        $name = esc_html($item->name);
        return "<strong>{$name}</strong>";
    }

    protected function column_short_code($item) {
        return
            '<p style="cursor:pointer;" alt="クリックしてコピーする" title="クリックしてコピーする" onclick="' .  "\n" .
            // seletionオブジェクトを取得
            'var selection = window.getSelection();' . "\n" .
            // rangeオブジェクトを生成
            'var range = document.createRange();' . "\n" .
            // rangeオブジェクトにp要素を与える
            'range.selectNodeContents(this);' . "\n" .
            // 一旦selectionオブジェクトの持つrangeオブジェクトを削除
            'selection.removeAllRanges();' . "\n" .
            // 上記で生成したrangeオブジェクトをselectionオブジェクトに改めて追加
            'selection.addRange(range);' . "\n" .
            // クリップボードにコピーします。
            'var succeeded = document.execCommand(' . "'copy'" . ');' . "\n" .
            // コピーに成功した場合の処理です。
            'if (succeeded) alert(' . "'ショートコードをコピーしました'" . ');' . "\n" .
            // selectionオブジェクトの持つrangeオブジェクトを全て削除 
            'selection.removeAllRanges();' . "\n" .
            '">[' . SimpleStripeCheckout::PLUGIN_ID . ' code=' . $item->code . ']</p>';
    }

    protected function handle_row_actions( $item, $column_name, $primary ) {
        if( $column_name === $primary ) {
            $actions = [
                'edit'   => '<a href="?page=' . SimpleStripeCheckout::SLUG__PRODUCT_EDIT_FORM . '&' . SimpleStripeCheckout::PARAMETER__PRODUCT_CODE . '=' . $item->code . '">編集</a>',
                'delete' => '<a href="?page=' . SimpleStripeCheckout::SLUG__PRODUCT_LIST                                    . '&action=delete&checked[]=' . $item->code . '">削除</a>'
            ];
            // div class = raw-actions がキモ
            return $this->row_actions($actions);
        }
    }

    /**
     * 並び替えのできるカラム
     */
    protected function get_sortable_columns() {
        return [
            'code'          => 'code',
            'short_code'    => 'short_code',
            'name'          => 'name',
            'provider_name' => 'provider_name',
            'price'         => 'price',
            'currency'      => 'currency',
            'button_name'   => 'button_name'
        ];
    }
}

class SimpleStripeCheckout {

    /**
     * このプラグインのバージョン
     */
    const VERSION = '1.0.0';

    /**
     * このプラグインのID：Growniche Simple Stripe Checkout
     */
    const PLUGIN_ID = 'gssc';

    /**
     * このプラグインのスクリプトのハンドル名
     */
    const SCRIPT_HANDLE = self::PLUGIN_ID . '-js';

    /**
     * CredentialAction（プレフィックス）
     */
    const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action_';

    /**
     * CredentialAction：初期設定
     */
    const CREDENTIAL_ACTION__INITIAL_CONFIG = self::CREDENTIAL_ACTION . 'initial-config';

    /**
     * CredentialAction：商品情報編集
     */
    const CREDENTIAL_ACTION__PRODUCT_EDIT = self::CREDENTIAL_ACTION . 'product-edit';

    /**
     * CredentialAction：メール設定
     */
    const CREDENTIAL_ACTION__MAIL_CONFIG = self::CREDENTIAL_ACTION . 'mail-config';

    /**
     * CredentialName（プレフィックス）
     */
    const CREDENTIAL_NAME = self::PLUGIN_ID . '-nonce-key_';

    /**
     * CredentialName：初期設定
     */
    const CREDENTIAL_NAME__INITIAL_CONFIG = self::CREDENTIAL_NAME . 'initial-config';

    /**
     * CredentialName：商品情報編集
     */
    const CREDENTIAL_NAME__PRODUCT_EDIT = self::CREDENTIAL_NAME . 'product-edit';

    /**
     * CredentialName：メール設定
     */
    const CREDENTIAL_NAME__MAIL_CONFIG = self::CREDENTIAL_NAME . 'mail-config';

    /**
     * (23文字)
     */
    const PLUGIN_PREFIX = self::PLUGIN_ID . '_';

    /**
     * OPTIONSキー：決済完了ページのSLUG
     * ※OPTIONSテーブルにセットする際のキー
     */
    const OPTION_KEY__CHECKEDOUT_PAGE_SLUG = self::PLUGIN_PREFIX . 'checkedout-page-slug';

    /**
     * OPTIONSキー：キャンセル完了ページのSLUG
     * ※OPTIONSテーブルにセットする際のキー
     */
    const OPTION_KEY__CANCEL_PAGE_SLUG = self::PLUGIN_PREFIX . 'cancel-page-slug';

    /**
     * OPTIONSキー：決済確定完了ページのSLUG
     * ※OPTIONSテーブルにセットする際のキー
     */
    const OPTION_KEY__CAPTURE_COMPLETE_PAGE_SLUG = self::PLUGIN_PREFIX . 'capture-complete-page-slug';

    /**
     * OPTIONSキー：[初期設定] STRIPEの公開キー
     * ※OPTIONSテーブルにセットする際のキー
     */
    const OPTION_KEY__STRIPE_PUBLIC_KEY = self::PLUGIN_PREFIX . 'stripe-public-key';

    /**
     * OPTIONSキー：[初期設定] STRIPEのシークレットキー
     */
    const OPTION_KEY__STRIPE_SECRET_KEY = self::PLUGIN_PREFIX . 'stripe-secret-key';

    /**
     * OPTIONSキー：[商品情報編集] 商品情報リスト
     */
    const OPTION_KEY__PRODUCT_LIST = self::PLUGIN_PREFIX . 'product-list';

    /**
     * OPTIONSキー：[メール設定] 販売者向け受信メルアド
     */
    const OPTION_KEY__SELLER_RECEIVE_ADDRESS = self::PLUGIN_PREFIX . 'seller_receive_address';

    /**
     * OPTIONSキー：[メール設定] 販売者向け送信元メルアド
     */
    const OPTION_KEY__SELLER_FROM_ADDRESS = self::PLUGIN_PREFIX . 'seller_from_address';

    /**
     * OPTIONSキー：[メール設定] 購入者向け送信元メルアド
     */
    const OPTION_KEY__BUYER_FROM_ADDRESS = self::PLUGIN_PREFIX . 'buyer_from_address';

    /**
     * OPTIONSキー：[メール設定] 即時決済フラグ
     */
    const OPTION_KEY__IMMEDIATE_SETTLEMENT = self::PLUGIN_PREFIX . 'immediate_settlement';

    /**
     * 画面のslug：トップ
     */
    const SLUG__TOP = self::PLUGIN_ID;

    /**
     * 画面のslug：初期設定
     */
    const SLUG__INITIAL_CONFIG_FORM = self::PLUGIN_PREFIX . 'initial-config-form';

    /**
     * 画面のslug：商品情報リスト
     */
    const SLUG__PRODUCT_LIST = self::PLUGIN_PREFIX . 'product-list';

    /**
     * 画面のslug：商品情報編集
     */
    const SLUG__PRODUCT_EDIT_FORM = self::PLUGIN_PREFIX . 'product-edit-form';

    /**
     * 画面のslug：メール設定
     */
    const SLUG__MAIL_CONFIG_FORM = self::PLUGIN_PREFIX . 'mail-config-form';

    /**
     * 画面のslug：STRIPE与信枠の確保
     */
    const SLUG__CHECKOUT = self::PLUGIN_PREFIX . 'checkout';

    /**
     * 画面のslug：STRIPE与信枠の確保キャンセル
     */
    const SLUG__REFUND = self::PLUGIN_PREFIX . 'refund';

    /**
     * 画面のslug：STRIPE決済完了（パーマリンクにアンスコを使用できなかったのでハイフンを使用）
     */
    const SLUG__CHECKEDOUT = self::PLUGIN_ID . '-' . 'checkedout';

    /**
     * 画面のslug：STRIPEキャンセル完了（パーマリンクにアンスコを使用できなかったのでハイフンを使用）
     */
    const SLUG__CANCEL = self::PLUGIN_ID . '-' . 'cancel';

    /**
     * 画面のslug：STRIPE決済の確定
     */
    const SLUG__CAPTURE = self::PLUGIN_PREFIX . 'capture';

    /**
     * 画面のslug：STRIPE決済の確定完了（パーマリンクにアンスコを使用できなかったのでハイフンを使用）
     */
    const SLUG__CAPTURE_COMPLETE = self::PLUGIN_ID . '-' . 'capture-complete';

    /**
     * パラメータ名：[初期設定] Stripeの公開キー
     */
    const PARAMETER__STRIPE_PUBLIC_KEY = self::PLUGIN_PREFIX . 'stripe-public-key';

    /**
     * パラメータ名：[初期設定] Stripeのシークレットキー
     */
    const PARAMETER__STRIPE_SECRET_KEY = self::PLUGIN_PREFIX . 'stripe-secret-key';

    /**
     * パラメータ名：[商品情報編集] 商品コード
     */
    const PARAMETER__PRODUCT_CODE = self::PLUGIN_PREFIX . 'product-code';

    /**
     * パラメータ名：[商品情報編集] 商品価格
     */
    const PARAMETER__PRODUCT_PRICE = self::PLUGIN_PREFIX . 'product-price';

    /**
     * パラメータ名：[商品情報編集] 商品提供者
     */
    const PARAMETER__PRODUCT_PROVIDER_NAME = self::PLUGIN_PREFIX . 'product-provider-name';

    /**
     * パラメータ名：[商品情報編集] 商品名
     */
    const PARAMETER__PRODUCT_NAME = self::PLUGIN_PREFIX . 'product-name';

    /**
     * パラメータ名：[商品情報編集] 商品通貨
     */
    const PARAMETER__PRODUCT_CURRENCY = self::PLUGIN_PREFIX . 'product-currency';

    /**
     * パラメータ名：[商品情報編集] 商品ボタン名
     */
    const PARAMETER__PRODUCT_BUTTON_NAME = self::PLUGIN_PREFIX . 'product-button-name';

    /**
     * パラメータ名：[メール設定] 販売者向け受信メルアド
     */
    const PARAMETER__SELLER_RECEIVE_ADDRESS = self::PLUGIN_PREFIX . 'seller-receive-address';

    /**
     * パラメータ名：[メール設定] 販売者向け送信元メルアド
     */
    const PARAMETER__SELLER_FROM_ADDRESS = self::PLUGIN_PREFIX . 'seller-from-address';

    /**
     * パラメータ名：[メール設定] 購入者向け送信元メルアド
     */
    const PARAMETER__BUYER_FROM_ADDRESS = self::PLUGIN_PREFIX . 'buyer-from-address';

    /**
     * パラメータ名：[メール設定] 即時決済フラグ
     */
    const PARAMETER__IMMEDIATE_SETTLEMENT = self::PLUGIN_PREFIX . 'immediate_settlement';

    /**
     * TRANSIENTキー(一時入力値)：[初期設定] Stripeの公開キー
     * ※4文字+41文字以下
     */
    const TRANSIENT_KEY__TEMP_PUBLIC_KEY = self::PLUGIN_PREFIX . 'temp-public-key';

    /**
     * TRANSIENTキー(一時入力値)：[初期設定] Stripeのシークレットキー
     */
    const TRANSIENT_KEY__TEMP_SECRET_KEY = self::PLUGIN_PREFIX . 'temp-secret-key';

    /**
     * TRANSIENTキー(一時入力値)：[商品情報編集] 商品コード
     */
    const TRANSIENT_KEY__TEMP_PRODUCT_CODE = self::PLUGIN_PREFIX . 'temp-product-code';

    /**
     * TRANSIENTキー(一時入力値)：[商品情報編集] 商品価格
     */
    const TRANSIENT_KEY__TEMP_PRODUCT_PRICE = self::PLUGIN_PREFIX . 'temp-product-price';

    /**
     * TRANSIENTキー(一時入力値)：[商品情報編集] 商品提供者名
     */
    const TRANSIENT_KEY__TEMP_PRODUCT_PROVIDER_NAME = self::PLUGIN_PREFIX . 'temp-product-provider-name';

    /**
     * TRANSIENTキー(一時入力値)：[商品情報編集] 商品名
     */
    const TRANSIENT_KEY__TEMP_PRODUCT_NAME = self::PLUGIN_PREFIX . 'temp-product-name';

    /**
     * TRANSIENTキー(一時入力値)：[商品情報編集] 商品通貨
     */
    const TRANSIENT_KEY__TEMP_PRODUCT_CURRENCY = self::PLUGIN_PREFIX . 'temp-product-currency';

    /**
     * TRANSIENTキー(一時入力値)：[商品情報編集] 商品ボタン名
     */
    const TRANSIENT_KEY__TEMP_PRODUCT_BUTTON_NAME = self::PLUGIN_PREFIX . 'temp-product-button-name';

    /**
     * TRANSIENTキー(一時入力値)：[メール設定] 販売者向け受信メルアド
     */
    const TRANSIENT_KEY__TEMP_SELLER_RECEIVE_ADDRESS = self::PLUGIN_PREFIX . 'temp-seller-receive-address';

    /**
     * TRANSIENTキー(一時入力値)：[メール設定] 販売者向け送信元メルアド
     */
    const TRANSIENT_KEY__TEMP_SELLER_FROM_ADDRESS = self::PLUGIN_PREFIX . 'temp-seller-from-address';

    /**
     * TRANSIENTキー(一時入力値)：[メール設定] 購入者向け送信元メルアド
     */
    const TRANSIENT_KEY__TEMP_BUYER_FROM_ADDRESS = self::PLUGIN_PREFIX . 'temp-buyer-from-address';

    /**
     * TRANSIENTキー(一時入力値)：[メール設定] 即時決済フラグ
     */
    const TRANSIENT_KEY__TEMP_IMMEDIATE_SETTLEMENT = self::PLUGIN_PREFIX . 'temp-immediate_settlement';

    /**
     * TRANSIENTキー(不正メッセージ)：[初期設定] Stripeの公開キー
     */
    const TRANSIENT_KEY__INVALID_PUBLIC_KEY = self::PLUGIN_PREFIX . 'invalid-public-key';

    /**
     * TRANSIENTキー(不正メッセージ)：[初期設定] Stripeのシークレットキー
     */
    const TRANSIENT_KEY__INVALID_SECRET_KEY = self::PLUGIN_PREFIX . 'invalid-secret-key';

    /**
     * TRANSIENTキー(不正メッセージ)：[商品情報編集] 商品価格
     */
    const TRANSIENT_KEY__INVALID_PRODUCT_PRICE = self::PLUGIN_PREFIX . 'invalid-product-price';

    /**
     * TRANSIENTキー(不正メッセージ)：[商品情報編集] 商品提供者名
     */
    const TRANSIENT_KEY__INVALID_PRODUCT_PROVIDER_NAME = self::PLUGIN_PREFIX . 'invalid-product-provider-name';

    /**
     * TRANSIENTキー(不正メッセージ)：[商品情報編集] 商品名
     */
    const TRANSIENT_KEY__INVALID_PRODUCT_NAME = self::PLUGIN_PREFIX . 'invalid-product-name';

    /**
     * TRANSIENTキー(不正メッセージ)：[商品情報編集] 商品通貨
     */
    const TRANSIENT_KEY__INVALID_PRODUCT_CURRENCY = self::PLUGIN_PREFIX . 'invalid-product-currency';

    /**
     * TRANSIENTキー(不正メッセージ)：[商品情報編集] 商品ボタン名
     */
    const TRANSIENT_KEY__INVALID_PRODUCT_BUTTON_NAME = self::PLUGIN_PREFIX . 'invalid-product-button-name';

    /**
     * TRANSIENTキー(不正メッセージ)：[メール設定] 販売者向け受信メルアド
     */
    const TRANSIENT_KEY__INVALID_SELLER_RECEIVE_ADDRESS = self::PLUGIN_PREFIX . 'invalid-seller-receive-address';

    /**
     * TRANSIENTキー(不正メッセージ)：[メール設定] 販売者向け送信元メルアド
     */
    const TRANSIENT_KEY__INVALID_SELLER_FROM_ADDRESS = self::PLUGIN_PREFIX . 'invalid-seller-from-address';

    /**
     * TRANSIENTキー(不正メッセージ)：[メール設定] 購入者向け送信元メルアド
     */
    const TRANSIENT_KEY__INVALID_BUYER_FROM_ADDRESS = self::PLUGIN_PREFIX . 'invalid-buyer-from-address';

    /**
     * TRANSIENTキー(不正メッセージ)：[メール設定] 即時決済フラグ
     */
    const TRANSIENT_KEY__INVALID_IMMEDIATE_SETTLEMENT = self::PLUGIN_PREFIX . 'invalid-immediate-settlement';

    /**
     * TRANSIENTキー(保存完了メッセージ)：初期設定
     */
    const TRANSIENT_KEY__SAVE_INITIAL_CONFIG = self::PLUGIN_PREFIX . 'save-initial-config';

    /**
     * TRANSIENTキー(保存完了メッセージ)：商品情報編集
     */
    const TRANSIENT_KEY__SAVE_PRODUCT_INFO = self::PLUGIN_PREFIX . 'save-product-info';

    /**
     * TRANSIENTキー(保存完了メッセージ)：メール設定
     */
    const TRANSIENT_KEY__SAVE_MAIL_CONFIG = self::PLUGIN_PREFIX . 'save-mail-config';

    /**
     * TRANSIENTのタイムリミット：5秒
     */
    const TRANSIENT_TIME_LIMIT = 5;

    /**
     * 通知タイプ：エラー
     */
    const NOTICE_TYPE__ERROR = 'error';

    /**
     * 通知タイプ：警告
     */
    const NOTICE_TYPE__WARNING = 'warning';

    /**
     * 通知タイプ：成功
     */
    const NOTICE_TYPE__SUCCESS = 'success';

    /**
     * 通知タイプ：情報
     */
    const NOTICE_TYPE__INFO = 'info';

    /**
     * 暗号化する時のパスワード：STRIPEの公開キーとシークレットキーの複合化で使用
     */
    const ENCRYPT_PASSWORD = 's9YQReXd';

    /**
     * 正規表現(部分)：メルアド
     */
    const REGEXP_ADDRESS = "[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*";

    /**
     * 正規表現：単一メルアド
     */
    const REGEXP_SINGLE_ADDRESS = "/^" . self::REGEXP_ADDRESS . "$/";

    /**
     * 正規表現：複数メルアド(カンマ区切り)
     */
    const REGEXP_MULTIPLE_ADDRESS = "/^" . self::REGEXP_ADDRESS . "([ ]*,[ ]*" . self::REGEXP_ADDRESS . ")*$/";

    /**
     * STRIPE対応通貨リスト
     */
    const STRIPE_CURRENCIES = array(
        'USD'=>array('label'=>'USD ($0.50以上)',    'min'=>0.50, 'format'=>'$___'),
        'AUD'=>array('label'=>'AUD ($0.50以上)',    'min'=>0.50, 'format'=>'$___'),
        'BRL'=>array('label'=>'BRL (R$0.50以上)',   'min'=>0.50, 'format'=>'R$___'),
        'CAD'=>array('label'=>'CAD ($0.50以上)',    'min'=>0.50, 'format'=>'$___'),
        'CHF'=>array('label'=>'CHF (0.50 Fr以上)',  'min'=>0.50, 'format'=>'___ Fr'),
        'DKK'=>array('label'=>'DKK (2.50-kr.以上)', 'min'=>2.50, 'format'=>'___-kr'),
        'EUR'=>array('label'=>'EUR (€0.50以上)',    'min'=>0.50, 'format'=>'€___'),
        'GBP'=>array('label'=>'GBP (£0.30以上)',    'min'=>0.30, 'format'=>'£___'),
        'HKD'=>array('label'=>'HKD ($4.00以上)',    'min'=>4.00, 'format'=>'$___'),
        'INR'=>array('label'=>'INR (₹0.50以上)',    'min'=>0.50, 'format'=>'₹___'),
        'JPY'=>array('label'=>'JPY (￥50以上)',     'min'=>50.0, 'format'=>'￥___'),
        'MXN'=>array('label'=>'MXN ($10以上)',      'min'=>10.0, 'format'=>'$___'),
        'MYR'=>array('label'=>'MYR (RM 2以上)',     'min'=>2.00, 'format'=>'RM ___'),
        'NOK'=>array('label'=>'NOK (3.00-kr.以上)', 'min'=>3.00, 'format'=>'___-kr.'),
        'NZD'=>array('label'=>'NZD ($0.50以上)',    'min'=>0.50, 'format'=>'$___'),
        'PLN'=>array('label'=>'PLN (2.00 zł以上)',  'min'=>2.00, 'format'=>'___ zł'),
        'SEK'=>array('label'=>'SEK (3.00-kr.以上)', 'min'=>3.00, 'format'=>'___-kr.'),
        'SGD'=>array('label'=>'SGD ($0.50以上)',    'min'=>0.50, 'format'=>'$___')
    );

    /**
     * WordPressの読み込みが完了してヘッダーが送信される前に実行するアクションにフックする、
     * SimpleStripeCheckoutクラスのインスタンスを生成するStatic関数
     */
    static function instance() {
        return new self();
    }

    /**
     * 固定ページのスラッグの重複回避処理
     * @param slug_prefix 重複を回避したいスラッグのプレフィックス
     * @param slug_suffix [参照渡し] 重複を回避した結果のスラッグのサフィックス
     * @param target 比較対象のスラッグ
     */
    static function avoidDuplication($slug_prefix, &$slug_suffix, $target) {
        preg_match('/^' . $slug_prefix . '(-([0-9]+))?$/', $target, $date_match);
        if (count($date_match) == 1) {
            if ($slug_suffix <= 2) {
                $slug_suffix = 2;
            }
        } else if (count($date_match) == 3) {
            if (intval($date_match[2]) > $slug_suffix) {
                $new_slug_suffix = intval($date_match[2]) + 1;
                if ($slug_suffix < $new_slug_suffix) {
                    $slug_suffix = $new_slug_suffix;
                }
            }
        }
    }

    /**
     * 通知タグを生成・取得
     * @param message 通知するメッセージ
     * @param type 通知タイプ(error/warning/success/info)
     * @retern 通知タグ(HTML)
     */
    static function getNotice($message, $type) {
        return 
            '<div class="notice notice-' . $type . ' is-dismissible">' .
            '<p><strong>' . $message . '</strong></p>' .
            '<button type="button" class="notice-dismiss">' .
            '<span class="screen-reader-text">Dismiss this notice.</span>' .
            '</button>' .
            '</div>';
    }

    /**
     * 複合化：AES 256
     * @param edata 暗号化してBASE64にした文字列
     * @param string 複合化のパスワード
     * @return 複合化された文字列
     */
    static function decrypt($edata, $password) {
        $data = base64_decode($edata);
        $salt = substr($data, 0, 16);
        $ct = substr($data, 16);
        $rounds = 3; // depends on key length
        $data00 = $password.$salt;
        $hash = array();
        $hash[0] = hash('sha256', $data00, true);
        $result = $hash[0];
        for ($i = 1; $i < $rounds; $i++) {
            $hash[$i] = hash('sha256', $hash[$i - 1].$data00, true);
            $result .= $hash[$i];
        }
        $key = substr($result, 0, 32);
        $iv  = substr($result, 32,16);
        return openssl_decrypt($ct, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * crypt AES 256
     *
     * @param data $data
     * @param string $password
     * @return base64 encrypted data
     */
    static function encrypt($data, $password) {
        // Set a random salt
        $salt = openssl_random_pseudo_bytes(16);
        $salted = '';
        $dx = '';
        // Salt the key(32) and iv(16) = 48
        while (strlen($salted) < 48) {
          $dx = hash('sha256', $dx.$password.$salt, true);
          $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32,16);
        $encrypted_data = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($salt . $encrypted_data);
    }

    /**
     * HTMLのOPTIONタグを生成・取得
     */
    static function makeHtmlSelectOptions($list, $selected, $label = null) {
        $html = '';
        foreach ($list as $key => $value) {
            $html .= '<option class="level-0" value="' . $key . '"';
            if ($key == $selected) {
                $html .= ' selected="selected';
            }
            $html .= '">' . (is_null($label) ? $value : $value[$label]) . '</option>';
        }
        return $html;
    }

    /**
     * 購入者向け与信枠確保メール本文テンプレート
     */
    static function buyer_mail_template($email, $service_name, $amount, $last4, $cancel_url, $site_name, $home_url, $buyer_from_address, $immediate_settlement) {
        return "
${email}様

この度はありがとうございます。
以下の内容でお支払いが行われました。

▼購入者様Eメール
${email}

▼内容
${service_name}

▼価格
${amount}

▼お支払いに使われたカード下四桁
${last4}
" . ($immediate_settlement ? "" : "
<<お支払いのキャンセルについて>>
お支払い確認後、24時間以内であれば
以下をクリックするとキャンセル可能です。
${cancel_url}

※それ以降はキャンセル料が発生します。
") . "
------
${site_name}
${home_url}

お支払いに関するお問い合わせ先
${buyer_from_address}
";
    }

    /**
     * 販売者向け与信枠確保メール本文テンプレート
     */
    static function seller_mail_template($email, $service_name, $amount, $last4, $capture_url, $site_name, $home_url, $immediate_settlement) {
        return "
お申込み内容

▼購入者様Eメール
${email}

▼内容
${service_name}

▼価格
${amount}

▼お支払いに使われたカード下四桁
${last4}
" . ($immediate_settlement ? "" : "
<<お支払いの確定をしなければ回収できません！>>
「24時間以内であればキャンセル可」と自動送信メールで伝えております。
以下をクリックすると確定できるので、24時間過ぎたらお願いいたします。
${capture_url}

※確定後にキャンセルがあった場合は、Stripeの手数料が掛かります。
") . "
------
${site_name}
${home_url}
";
    }

    /**
     * 購入者向けキャンセルメール本文テンプレート
     */
    static function buyer_cancel_mail_template($email, $service_name, $amount, $last4, $site_name, $home_url, $buyer_from_address) {
        return "
${email}様

以下の内容についてキャンセルがされました。
再度お申込みの際は、再度お支払いが必要です。

▼購入者様Eメール
${email}

▼内容
${service_name}

▼価格
${amount}

▼お支払いに使われたカード下四桁
${last4}

------
${site_name}
${home_url}

お支払いに関するお問い合わせ先
${buyer_from_address}
";
    }

    /**
     * 販売者向けキャンセルメール本文テンプレート
     */
    static function seller_cancel_mail_template($email, $service_name, $amount, $last4, $site_name, $home_url) {
        return "
以下のお支払い登録がキャンセルされました。

▼購入者様Eメール
${email}

▼内容
${service_name}

▼価格
${amount}

▼お支払いに使われたカード下四桁
${last4}

------
${site_name}
${home_url}
";
    }

    /**
     * 購入者向け決済確定メール本文テンプレート
     */
    static function buyer_capture_mail_template($email, $service_name, $amount, $last4, $site_name, $home_url) {
        return "
${email}様

この度は、誠にありがとうございます。

キャンセルがありませんでしたので、
以下の内容にて、お支払いが確定されました。

▼購入者様Eメール
${email}

▼内容
${service_name}

▼価格
${amount}

▼お支払いに使われたカード下四桁
${last4}

------
${site_name}
${home_url}
";
    }

    /**
     * 販売者向け決済確定メール本文テンプレート
     */
    static function seller_capture_mail_template($email, $service_name, $amount, $last4, $site_name, $home_url) {
        return "
お客様にて、お支払いの確定がありましたので、
以下のお支払いが行われました。

▼購入者様Eメール
${email}

▼内容
${service_name}

▼価格
${amount}

▼お支払いに使われたカード下四桁
${last4}

------
${site_name}
${home_url}
";
    }

    /**
     * メール送信処理
     */
    static function send_mail($title, $body, $to, $from) {
        // メールヘッダー
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");
        $header  = "From: " . $from . "\n";
        $header = $header . "Reply-To: " . $from;
        // メール送信
        return mb_send_mail($to, $title, $body, $header, "-f" .$from);
    }
    
    /**
     * 商品情報リストテーブル
     */
    private $product_list_table;

    /**
     * コンストラクタ
     */
    function __construct() {
        
        // ショートコード処理の前準備
        add_action('wp_enqueue_scripts', [$this, 'pre_short_code']);
        wp_enqueue_script('jquery' );
        // ショートコード処理を登録
        add_shortcode(self::PLUGIN_ID, [$this, 'short_code']);

        // STRIPEの与信枠の確保処理の準備：
        add_rewrite_endpoint(self::SLUG__CHECKOUT, EP_ALL);
        // STRIPEの与信枠の確保キャンセル処理の準備：
        add_rewrite_endpoint(self::SLUG__REFUND, EP_ALL);
        // STRIPEの決済の確定処理の準備：
        add_rewrite_endpoint(self::SLUG__CAPTURE, EP_ALL);

        // ページを表示する直前の前処理をフック(STRIPEのチェックアウト処理を追加)
        add_action('template_redirect', [$this, 'on_template_redirect']);

        // 管理画面を表示中、且つ、ログイン済の場合
        if (is_admin() && is_user_logged_in()) {
            
            // 管理画面メニューの基本構造が配置された後に実行するアクションに、
            // 管理画面のトップメニューページを追加する関数をフック
            add_action('admin_menu', [$this, 'set_plugin_menu']);
            // 管理画面メニューの基本構造が配置された後に実行するアクションに、
            // 管理画面のサブメニューページを追加する関数をフック
            add_action('admin_menu', [$this, 'set_plugin_sub_menu']);
            
            // 管理画面各ページの最初、ページがレンダリングされる前に実行するアクションに、
            // 初期設定を保存する関数をフック
            add_action('admin_init', [$this, 'save_initial_config']);
            // 管理画面各ページの最初、ページがレンダリングされる前に実行するアクションに、
            // 商品情報を保存する関数をフック
            add_action('admin_init', [$this, 'save_product']);
            // 管理画面各ページの最初、ページがレンダリングされる前に実行するアクションに、
            // 初期設定を保存する関数をフック
            add_action('admin_init', [$this, 'save_mail_config']);
            
        }
    }

    /**
     * ショートコード処理の前準備
     */
    function pre_short_code() {
        wp_enqueue_script(self::SCRIPT_HANDLE, plugin_dir_url(__FILE__) . self::PLUGIN_ID . '.js');
    }

    /**
     * ショートコード処理
     */
    function short_code($atts, $content = null) {
        $val = '';
        // ショートコードの引数に商品コードがある場合
        if (is_array($atts) && isset($atts['code']) && intval($atts['code']) > 0) {
            // 即時決済フラグをOPTIONSテーブルから取得
            $immediate_settlement = get_option(self::OPTION_KEY__IMMEDIATE_SETTLEMENT);
            // 即時決済フラグが設定されている場合
            if ($immediate_settlement === 'ON' || $immediate_settlement === 'OFF') {
                // 商品コード
                $product_code = intval($atts['code']);
                // STRIPEの公開キーをOPTIONSテーブルから取得
                $stripe_public_key = self::decrypt(get_option(self::OPTION_KEY__STRIPE_PUBLIC_KEY), self::ENCRYPT_PASSWORD);
                // 商品情報リストをOPTIONSテーブルから取得
                $product_list = get_option(self::OPTION_KEY__PRODUCT_LIST);
                // 商品情報リストがある場合
                if (!is_null($product_list)) {
                    // 商品情報リストをアンシリアライズ
                    $product_list = unserialize($product_list);
                    // アンシリアライズした商品情報リストが正しく配列の場合
                    if (is_array($product_list)) {
                        for ($i = 0; $i < count($product_list); $i++) {
                            if ($product_list[$i] instanceof SimpleStripeCheckout_Product) {
                                // 商品コードが一致する場合
                                if ($product_list[$i]->code == $product_code) {
                                    $product = $product_list[$i];
                                }
                            }
                        }
                    }
                }
                // 商品情報がある場合
                if (isset($product)) {
                    // 商品価格
                    $product_price = $product->price;
                    // 商品通貨
                    $product_currency = strtolower($product->currency);
                    // 商品提供者名
                    $product_provider_name = $product->provider_name;
                    // 商品名
                    $product_name = $product->name;
                    // 商品ボタン名
                    $product_button_name = $product->button_name;
                    // 決済URL
                    $url = '?' . self::SLUG__CHECKOUT . "=" . $product->code;
                    // STRIPEの購入ボタンのHTMLを作成
                    // <script
                    //  src="https://checkout.stripe.com/checkout.js" 
                    //  class="stripe-button" 
                    //  data-key="{$stripe_public_key}" 
                    //  data-amount="{$product_price}" 
                    //  data-name="{$product_provider_name}" 
                    //  data-description="{$product_name}" 
                    //  data-image="https://stripe.com/img/documentation/checkout/marketplace.png" 
                    //  data-locale="auto" 
                    //  data-currency="{$product_currency}" 
                    //  data-zip-code="false" 
                    //  data-allow-remember-me="false" 
                    //  data-label="{$product_button_name}"></script>
                    $val =<<<EOS
                    <form action="{$url}" method="POST" class="gssc-form"
                      data-key="{$stripe_public_key}" 
                      data-amount="{$product_price}" 
                      data-name="{$product_provider_name}" 
                      data-description="{$product_name}" 
                      data-currency="{$product_currency}" 
                      data-label="{$product_button_name}">
                    </form>
EOS;
                }
            }
        }
        return $val;
    }

    /**
     * ページを表示する直前の前処理をフック(STRIPEのチェックアウト処理を追加)
     */
    function on_template_redirect() {
        // STRIPEの与信枠の確保をするために対象の商品コードをURLクエリーから取得
        $checkout = get_query_var(self::SLUG__CHECKOUT);
        // 商品コードを取得
        $product_code = intval($checkout);
        // 商品コードがある場合
        if ($product_code > 0) {
            // STRIPEの与信枠の確保処理
            $this->checkout($product_code);
        }
        // STRIPEの与信枠の確保キャンセル処理をするために対象の料金IDをURLクエリーから取得
        // ex) ch_zd4gENPfuT06SdQwWrsn
        $refund = get_query_var(self::SLUG__REFUND);
        // 与信枠の確保キャンセルの場合
        // ex) ch_zd4gENPfuT06SdQwWrsn
        if (strlen($refund) > 0) {
            // STRIPEの与信枠の確保キャンセル処理
            $this->refund($refund);
        }
        // STRIPEの決済の確定処理をするために対象の料金IDをURLクエリーから取得
        // ex) ch_zd4gENPfuT06SdQwWrsn
        $capture = get_query_var(self::SLUG__CAPTURE);
        // 決済確定の場合
        if (strlen($capture) > 0) {
            // STRIPEの決済を確定処理
            $this->capture($capture);
        }
        
    }

    /**
     * STRIPEの与信枠の確保処理
     */
    function checkout($product_code) {
        // STRIPEのライブラリを読み込む
        require_once( dirname(__FILE__).'/lib/stripe-php-7.7.1/init.php');
        // STRIPEのシークレットキーをOPTIONSテーブルから取得
        $stripe_secret_key = self::decrypt(get_option(self::OPTION_KEY__STRIPE_SECRET_KEY), self::ENCRYPT_PASSWORD);
        // STRIPEのシークレットキーをセット
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        // STRIPEのトークンと決済者のメルアドを取得
        $token = trim(sanitize_text_field($_POST['stripeToken']));
        $email = trim(sanitize_text_field($_POST['stripeEmail']));
        // 商品情報リストをOPTIONSテーブルから取得
        $product_list = get_option(self::OPTION_KEY__PRODUCT_LIST);
        // 商品情報リストがある場合
        if (!is_null($product_list)) {
            // 商品情報リストをアンシリアライズ
            $product_list = unserialize($product_list);
            // アンシリアライズした商品情報リストが正しく配列の場合
            if (is_array($product_list)) {
                for ($i = 0; $i < count($product_list); $i++) {
                    if ($product_list[$i] instanceof SimpleStripeCheckout_Product) {
                        // 商品コードが一致する場合
                        if ($product_list[$i]->code == $product_code) {
                            $product = $product_list[$i];
                        }
                    }
                }
            }
        }
        // 商品情報がない場合
        if (!isset($product)) {
            echo '商品情報がありません';
            exit;
        }
        // 即時決済フラグをOPTIONSテーブルから取得
        $immediate_settlement = get_option(self::OPTION_KEY__IMMEDIATE_SETTLEMENT);
        // 即時決済フラグが設定されていない場合
        if ($immediate_settlement !== 'ON' && $immediate_settlement !== 'OFF') {
            echo '設定が不完全です';
            exit;
        }
        $immediate_settlement = ($immediate_settlement === 'ON');
        // 決済結果
        $charge = null;
        // 料金ID
        $charge_id = null;
        // フォームから情報を取得:
        try {
            // オーソリ(与信枠の確保)
            $charge = \Stripe\Charge::create(array(
                "amount" => $product->price,
                "currency" => strtolower($product->currency),
                "source" => $token,
                "description" => $product->name,
                // 即時決済フラグがONの場合はtrue(即座に決済が完了)
                // 即時決済フラグがOFFの場合はfalse(与信枠を確保した後、決済を確定させるかキャンセル)
                'capture' => $immediate_settlement,
            ));
            // 料金IDを取得
            $charge_id = $charge['id'];
        } catch (\Stripe\Error\Card $e) {
            if ($charge_id !== null) {
                // 例外が発生すればオーソリを取り消す
                \Stripe\Refund::create(array(
                    'charge' => $charge_id,
                ));
            }
            // 決済できなかったときの処理
            die('決済が完了しませんでした');
        }
        // カード番号下4桁
        $last4 = "----";
        // 金額
        $amount = 0;
        // サービス名
        $service_name = '----';
        // メルアド
        $email = '----';
        // 決済が完了した場合
        if ($charge) {
            // 金額を取得
            if (isset($charge->amount)) {
                $amount = $charge->amount;
            }
            // サービス名を取得
            if (isset($charge->description)) {
                $service_name = $charge->description;
            }
            if (isset($charge->source)) {
                // カード番号下4桁を取得
                if (isset($charge->source->last4)) {
                    $last4 = $charge->source->last4;
                }
                // メルアドを取得
                if (isset($charge->source->name)) {
                    $email = $charge->source->name;
                }
            }
        }
        $amount = str_replace('___', $amount, self::STRIPE_CURRENCIES[strtoupper($product->currency)]['format']);
        // キャンセルURL
        $cancel_url = home_url() . '/?' . self::SLUG__REFUND . '=' . $charge_id;
        // 確定URL
        $capture_url = home_url() . '/?' . self::SLUG__CAPTURE . '=' . $charge_id;
        // サイト名
        $site_name = get_bloginfo('name');
        // サイトURL
        $home_url = home_url();
        // 購入者向けメール送信
        if (self::send_mail(
            // タイトル
            "${service_name}へのお支払いありがとうございます。",
            // 本文
            self::buyer_mail_template($email, $service_name, $amount, $last4, $cancel_url, $site_name, $home_url, $buyer_from_address, $immediate_settlement),
            // 宛先
            $email,
            // 送信元(購入者向け送信元メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__BUYER_FROM_ADDRESS)
        )) {
            echo "メールを送信しました";
        } else {
            echo "メールの送信に失敗しました";
        }
        // 販売者向けメール送信
        if (self::send_mail(
            // タイトル
            "${service_name}へのお支払いがありました。",
            // 本文
            self::seller_mail_template($email, $service_name, $amount, $last4, $capture_url, $site_name, $home_url, $immediate_settlement),
            // 宛先(販売者向け受信メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__SELLER_RECEIVE_ADDRESS),
            // 送信元(販売者向け送信元メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__SELLER_FROM_ADDRESS)
        )) {
            echo "メールを送信しました";
        } else {
            echo "メールの送信に失敗しました";
        };
        // サンキューページへリダイレクト
        $checkedout_page_slug = get_option(SimpleStripeCheckout::OPTION_KEY__CHECKEDOUT_PAGE_SLUG);
        if (strlen($checkedout_page_slug) === 0) {
            $checkedout_page_slug = SimpleStripeCheckout::SLUG__CHECKEDOUT;
        }
        wp_safe_redirect(home_url('/' . $checkedout_page_slug), 303);
        exit;
    }

    /**
     * STRIPEの与信枠の確保キャンセル処理
     */
    function refund($charge_id) {
        // STRIPEのライブラリを読み込む
        require_once( dirname(__FILE__).'/lib/stripe-php-7.7.1/init.php');
        // STRIPEのシークレットキーをOPTIONSテーブルから取得
        $stripe_secret_key = self::decrypt(get_option(self::OPTION_KEY__STRIPE_SECRET_KEY), self::ENCRYPT_PASSWORD);
        // STRIPEのシークレットキーをセット
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        // 決済結果
        $charge = null;
        try {
            // 与信枠を確保していた料金データを取得
            $charge = \Stripe\Charge::retrieve($charge_id);
            // キャンセル済の場合
            if ($charge['refunded'] === true) {
                die('決済は既にキャンセルされています。');
            }
            // 決済確定済の場合
            if ($charge['captured'] === true) {
                die('決済は既に確定されています。');
            }
            // 24時間未経過の場合
            if (($charge['created'] + (60 * 60 * 24)) < time()) {
                die('24時間を経過している為、キャンセルできません。');
            }
            // 与信枠の確保をキャンセル
            \Stripe\Refund::create(array(
                'charge' => $charge['id'],
            ));
        } catch (Exception $e) {
            die('与信枠を確保していたデータがないか、与信枠の確保のキャンセルに失敗しました。');
        }
        echo '与信枠の確保をキャンセルしました。';
        
        // カード番号下4桁
        $last4 = "----";
        // 金額
        $amount = 0;
        // サービス名
        $service_name = '----';
        // メルアド
        $email = '----';
        // 通貨
        $currency = '----';
        // 決済が完了した場合
        if ($charge) {
            // 金額を取得
            if (isset($charge->amount)) {
                $amount = $charge->amount;
            }
            // サービス名を取得
            if (isset($charge->description)) {
                $service_name = $charge->description;
            }
            // 通貨を取得
            if (isset($charge->currency)) {
                $currency = $charge->currency;
            }
            if (isset($charge->source)) {
                // カード番号下4桁を取得
                if (isset($charge->source->last4)) {
                    $last4 = $charge->source->last4;
                }
                // メルアドを取得
                if (isset($charge->source->name)) {
                    $email = $charge->source->name;
                }
            }
        }
        // 価格に単位を付ける
        $amount = str_replace('___', $amount, self::STRIPE_CURRENCIES[strtoupper($currency)]['format']);
        // サイト名
        $site_name = get_bloginfo('name');
        // サイトURL
        $home_url = home_url();
        // 購入者向けメール送信
        if (self::send_mail(
            // タイトル
            "${service_name}がキャンセルされました。",
            // 本文
            self::buyer_cancel_mail_template($email, $service_name, $amount, $last4, $site_name, $home_url, $buyer_from_address),
            // 宛先
            $email,
            // 送信元(購入者向け送信元メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__BUYER_FROM_ADDRESS)
        )) {
            echo "メールを送信しました";
        } else {
            echo "メールの送信に失敗しました";
        }
        // 販売者向けメール送信
        if (self::send_mail(
            // タイトル
            "${service_name}がキャンセルされました。",
            // 本文
            self::seller_cancel_mail_template($email, $service_name, $amount, $last4, $site_name, $home_url),
            // 宛先(販売者向け受信メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__SELLER_RECEIVE_ADDRESS),
            // 送信元(販売者向け送信元メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__SELLER_FROM_ADDRESS)
        )) {
            echo "メールを送信しました";
        } else {
            echo "メールの送信に失敗しました";
        };
        // キャンセル完了ページへリダイレクト
        $cancel_page_slug = get_option(SimpleStripeCheckout::OPTION_KEY__CANCEL_PAGE_SLUG );
        if (strlen($cancel_page_slug) === 0) {
            $cancel_page_slug = SimpleStripeCheckout::SLUG__CANCEL;
        }
        wp_safe_redirect(home_url('/' . $cancel_page_slug), 303);
        exit;
    }

    /**
     * STRIPEの決済の確定処理
     */
    function capture($charge_id) {
        // STRIPEのライブラリを読み込む
        require_once( dirname(__FILE__).'/lib/stripe-php-7.7.1/init.php');
        // STRIPEのシークレットキーをOPTIONSテーブルから取得
        $stripe_secret_key = self::decrypt(get_option(self::OPTION_KEY__STRIPE_SECRET_KEY), self::ENCRYPT_PASSWORD);
        // STRIPEのシークレットキーをセット
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        // 決済結果
        $charge = null;
        // 与信枠を確保していた料金データを取得
        try {
            $charge = \Stripe\Charge::retrieve($charge_id);
            // キャンセル済の場合
            if ($charge['refunded'] === true) {
                die('決済は既にキャンセルされています。');
            }
            // 決済確定済の場合
            if ($charge['captured'] === true) {
                die('決済は既に確定されています。');
            }
            // 24時間未経過の場合
            if (($charge['created'] + (60 * 60 * 24)) > time()) {
                die('まだ24時間経過していません。');
            }
            // 決済を確定
            $charge->capture();
        } catch (\Stripe\Error\Card $e) {
            die('決済を確定するデータがないか、決済の確定に失敗しました。');
        }
        echo '決済を確定しました。';
        // カード番号下4桁
        $last4 = "----";
        // 金額
        $amount = 0;
        // サービス名
        $service_name = '----';
        // メルアド
        $email = '----';
        // 通貨
        $currency = '----';
        // 決済が完了した場合
        if ($charge) {
            // 金額を取得
            if (isset($charge->amount)) {
                $amount = $charge->amount;
            }
            // サービス名を取得
            if (isset($charge->description)) {
                $service_name = $charge->description;
            }
            // 通貨を取得
            if (isset($charge->currency)) {
                $currency = $charge->currency;
            }
            if (isset($charge->source)) {
                // カード番号下4桁を取得
                if (isset($charge->source->last4)) {
                    $last4 = $charge->source->last4;
                }
                // メルアドを取得
                if (isset($charge->source->name)) {
                    $email = $charge->source->name;
                }
            }
        }
        $amount = str_replace('___', $amount, self::STRIPE_CURRENCIES[strtoupper($currency)]['format']);
        // サイト名
        $site_name = get_bloginfo('name');
        // サイトURL
        $home_url = home_url();
        // 購入者向けメール送信
        if (self::send_mail(
            // タイトル
            "${service_name}の支払いが行われました。",
            // 本文
            self::buyer_capture_mail_template($email, $service_name, $amount, $last4, $cancel_url, $site_name, $home_url, $buyer_from_address),
            // 宛先
            $email,
            // 送信元(購入者向け送信元メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__BUYER_FROM_ADDRESS)
        )) {
            echo "メールを送信しました";
        } else {
            echo "メールの送信に失敗しました";
        }
        // 販売者向けメール送信
        if (self::send_mail(
            // タイトル
            "${service_name}の支払いが行われました。",
            // 本文
            self::seller_capture_mail_template($email, $service_name, $amount, $last4, $site_name, $home_url),
            // 宛先(販売者向け受信メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__SELLER_RECEIVE_ADDRESS),
            // 送信元(販売者向け送信元メルアドをOPTIONSテーブルから取得)
            get_option(self::OPTION_KEY__SELLER_FROM_ADDRESS)
        )) {
            echo "メールを送信しました。";
        } else {
            echo "メールの送信に失敗しました。";
        };
        // 決済確定完了ページへリダイレクト
        $capture_complete_page_slug = get_option(SimpleStripeCheckout::OPTION_KEY__CAPTURE_COMPLETE_PAGE_SLUG );
        if (strlen($capture_complete_page_slug) === 0) {
            $capture_complete_page_slug = SimpleStripeCheckout::SLUG__CAPTURE_COMPLETE;
        }
        wp_safe_redirect(home_url('/' . $capture_complete_page_slug), 303);
        exit;
    }

    /**
     * 管理画面メニューの基本構造が配置された後に実行するアクションにフックする、
     * 管理画面のトップメニューページを追加する関数
     */
    function set_plugin_menu() {
        // トップメニュー「SimpleStripeCheckout」を追加
        add_menu_page(
            // ページタイトル：
            'SimpleStripeCheckout',
            // メニュータイトル：
            'Simple Stripe Checkout',
            // 権限：
            // manage_optionsは以下の管理画面設定へのアクセスを許可
            // ・設定 > 一般設定
            // ・設定 > 投稿設定
            // ・設定 > 表示設定
            // ・設定 > ディスカッション
            // ・設定 > パーマリンク設定
            'manage_options',
            // ページを開いたときのURL(slug)：
            self::SLUG__TOP,
            // メニューに紐づく画面を描画するcallback関数：
            // [$this, 'show_about_plugin'],
            [$this, 'show_product_list'],
            // アイコン：
            // WordPressが用意しているカートのアイコン
            // ・参考（https://developer.wordpress.org/resource/dashicons/#awards）
            'dashicons-cart',
            // メニューが表示される位置：
            // 省略時はメニュー構造の最下部に表示される。
            // 大きい数値ほど下に表示される。
            // 2つのメニューが同じ位置を指定している場合は片方のみ表示され上書きされる可能性がある。
            // 衝突のリスクは整数値でなく小数値を使用することで回避することができる。
            // 例： 63の代わりに63.3（コード内ではクォートを使用。例えば '63.3'）
            // 初期値はメニュー構造の最下部。
            // ・2 - ダッシュボード
            // ・4 - （セパレータ）
            // ・5 - 投稿
            // ・10 - メディア
            // ・15 - リンク
            // ・20 - 固定ページ
            // ・25 - コメント
            // ・59 - （セパレータ）
            // ・60 - 外観（テーマ）
            // ・65 - プラグイン
            // ・70 - ユーザー
            // ・75 - ツール
            // ・80 - 設定
            // ・99 - （セパレータ）
            // 但しネットワーク管理者メニューでは値が以下の様に異なる。
            // ・2 - ダッシュボード
            // ・4 - （セパレータ）
            // ・5 - 参加サイト
            // ・10 - ユーザー
            // ・15 - テーマ
            // ・20 - プラグイン
            // ・25 - 設定
            // ・30 - 更新
            // ・99 - （セパレータ）
            99
        );
    }

    /**
     * 管理画面メニューの基本構造が配置された後に実行するアクションにフックする、
     * 管理画面のサブメニューページを追加する関数
     */
    function set_plugin_sub_menu() {
        
        // サブメニュー「初期設定」を追加
        add_submenu_page(
            // 親メニューのslug：
            self::SLUG__TOP,
            //ページタイトル：
            '初期設定',
            //メニュータイトル：
            '初期設定',
            // 権限：
            // manage_optionsは以下の管理画面設定へのアクセスを許可
            // ・設定 > 一般設定
            // ・設定 > 投稿設定
            // ・設定 > 表示設定
            // ・設定 > ディスカッション
            // ・設定 > パーマリンク設定
            'manage_options',
            // ページを開いたときのURL(slug)：
            self::SLUG__INITIAL_CONFIG_FORM,
            // メニューに紐づく画面を描画するcallback関数：
            [$this, 'show_initial_config_form']
        );
        
        // 商品情報リストクラスを生成
        $this->product_list_table = new SimpleStripeCheckout_ProductListTable();
        // 商品情報リストのアクション処理
        $this->act_to_products();
        // サブメニュー「商品一覧」を追加
        add_submenu_page(
            // 親メニューのslug：
            self::SLUG__TOP,
            //ページタイトル：
            '商品一覧',
            //メニュータイトル：
            '商品一覧',
            // 権限：
            // manage_optionsは以下の管理画面設定へのアクセスを許可
            // ・設定 > 一般設定
            // ・設定 > 投稿設定
            // ・設定 > 表示設定
            // ・設定 > ディスカッション
            // ・設定 > パーマリンク設定
            'manage_options',
            // ページを開いたときのURL(slug)：
            self::SLUG__PRODUCT_LIST,
            // メニューに紐づく画面を描画するcallback関数：
            [$this, 'show_product_list']
        );
        
        // サブメニュー「新規登録」を追加
        add_submenu_page(
            // 親メニューのslug：
            self::SLUG__TOP,
            //ページタイトル：
            '新規登録',
            //メニュータイトル：
            '新規登録',
            // 権限：
            // manage_optionsは以下の管理画面設定へのアクセスを許可
            // ・設定 > 一般設定
            // ・設定 > 投稿設定
            // ・設定 > 表示設定
            // ・設定 > ディスカッション
            // ・設定 > パーマリンク設定
            'manage_options',
            // ページを開いたときのURL(slug)：
            self::SLUG__PRODUCT_EDIT_FORM,
            // メニューに紐づく画面を描画するcallback関数：
            [$this, 'show_product_edit_form']
        );
        
        // サブメニュー「メール設定」を追加
        add_submenu_page(
            // 親メニューのslug：
            self::SLUG__TOP,
            //ページタイトル：
            'メール設定',
            //メニュータイトル：
            'メール設定',
            // 権限：
            // manage_optionsは以下の管理画面設定へのアクセスを許可
            // ・設定 > 一般設定
            // ・設定 > 投稿設定
            // ・設定 > 表示設定
            // ・設定 > ディスカッション
            // ・設定 > パーマリンク設定
            'manage_options',
            // ページを開いたときのURL(slug)：
            self::SLUG__MAIL_CONFIG_FORM,
            // メニューに紐づく画面を描画するcallback関数：
            [$this, 'show_mail_config_form']
        );
    }

    /**
     * 商品情報リストをOPTIONSから取得
     */
    function get_product_list() {
        // 商品コードがあればoptionsテーブルから商品情報を取得
        $product_list = get_option(self::OPTION_KEY__PRODUCT_LIST);
        // 商品情報リストがある場合
        if (!is_null($product_list)) {
            // 商品情報リストをアンシリアライズ
            $product_list = unserialize($product_list);
        }
        // 商品情報リストが正しく配列ではない場合
        if (!is_array($product_list)) {
            $product_list = array();
        }
        return $product_list;
    }

    /**
     * トップメニュー「SimpleStripeCheckout」押下時の画面を表示するcallback関数
     */
    function show_about_plugin() {
        echo "<h1>Simple Stripe Checkout</h1>";
        echo "<p>決済プラットフォーム「Stripe」の連携プラグインです。</p>";
    }

    /**
     * サブメニュー「初期設定」押下時の画面を表示するcallback関数
     */
    function show_initial_config_form() {
        // 初期設定の保存完了メッセージ
        if (false !== ($complete_message = get_transient(self::TRANSIENT_KEY__SAVE_INITIAL_CONFIG))) {
            $complete_message = self::getNotice($complete_message, self::NOTICE_TYPE__SUCCESS);
        }
        // STRIPEの公開キーの不正メッセージ
        if (false !== ($invalid_public_key = get_transient(self::TRANSIENT_KEY__INVALID_PUBLIC_KEY))) {
            $invalid_public_key = self::getNotice($invalid_public_key, self::NOTICE_TYPE__ERROR);
        }
        // STRIPEのシークレットキーの不正メッセージ
        if (false !== ($invalid_secret_key = get_transient(self::TRANSIENT_KEY__INVALID_SECRET_KEY))) {
            $invalid_secret_key = self::getNotice($invalid_secret_key, self::NOTICE_TYPE__ERROR);
        }
        // STRIPEの公開キーのパラメータ名
        $param_stripe_public_key = self::PARAMETER__STRIPE_PUBLIC_KEY;
        // STRIPEのシークレットキーのパラメータ名
        $param_stripe_secret_key = self::PARAMETER__STRIPE_SECRET_KEY;
        // STRIPEの公開キーをTRANSIENTから取得
        if (false === ($stripe_public_key = get_transient(self::TRANSIENT_KEY__TEMP_PUBLIC_KEY))) {
            // 無ければoptionsテーブルから取得
            $stripe_public_key = self::decrypt(get_option(self::OPTION_KEY__STRIPE_PUBLIC_KEY), self::ENCRYPT_PASSWORD);
        }
        // STRIPEのシークレットキーをoptionsテーブルから取得
        if (false === ($stripe_secret_key = get_transient(self::TRANSIENT_KEY__TEMP_SECRET_KEY))) {
            // 無ければoptionsテーブルから取得
            $stripe_secret_key = self::decrypt(get_option(self::OPTION_KEY__STRIPE_SECRET_KEY), self::ENCRYPT_PASSWORD);
        }
        // nonceフィールドを生成・取得
        $nonce_field = wp_nonce_field(self::CREDENTIAL_ACTION__INITIAL_CONFIG, self::CREDENTIAL_NAME__INITIAL_CONFIG, true, false);
        // 送信ボタンを生成・取得
        $submit_button = get_submit_button('保存');
        // HTMLを出力
        echo <<< EOM
            <div class="wrap">
            <h2>初期設定</h2>
            {$complete_message}
            {$invalid_public_key}
            {$invalid_secret_key}
            <form action="" method='post' id="simple-stripe-checkout-initial-config-form">
                {$nonce_field}
                <p>
                    <label for="{$param_stripe_public_key}">公開キー：</label>
                    <input type="password" name="{$param_stripe_public_key}" value="{$stripe_public_key}"/>
                </p>
                <p>
                    <label for="{$param_stripe_secret_key}">シークレットキー：</label>
                    <input type="password" name="{$param_stripe_secret_key}" value="{$stripe_secret_key}"/>
                </p>
                {$submit_button}
            </form>
            </div>
EOM;
    }

    /**
     * 初期設定を保存するcallback関数
     */
    function save_initial_config() {
        // nonceで設定したcredentialをPOST受信した場合
        if (isset($_POST[self::CREDENTIAL_NAME__INITIAL_CONFIG]) && $_POST[self::CREDENTIAL_NAME__INITIAL_CONFIG]) {
            // nonceで設定したcredentialのチェック結果が問題ない場合
            if (check_admin_referer(self::CREDENTIAL_ACTION__INITIAL_CONFIG, self::CREDENTIAL_NAME__INITIAL_CONFIG)) {
                // STRIPEの公開キーをPOSTから取得
                $stripe_public_key = trim(sanitize_text_field($_POST[self::PARAMETER__STRIPE_PUBLIC_KEY]));
                // STRIPEのシークレットキーをPOSTから取得
                $stripe_secret_key = trim(sanitize_text_field($_POST[self::PARAMETER__STRIPE_SECRET_KEY]));
                $valid = true;
                // STRIPEの公開キーが正しくない場合
                if (!preg_match("/^[0-9a-zA-Z_]+$/", $stripe_public_key)) {
                    // STRIPEの公開キーの設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_PUBLIC_KEY, "Stripeの公開キーが正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // STRIPEのシークレットキーが正しくない場合
                if (!preg_match("/^[0-9a-zA-Z_]+$/", $stripe_secret_key)) {
                    // STRIPEのシークレットキーの設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_SECRET_KEY, "Stripeのシークレットキーが正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 有効フラグがTrueの場合(STRIPEの公開キーとシークレットキーが正しい場合)
                if ($valid) {
                    // 保存処理
                    // Stripeの公開キーをoptionsテーブルに保存
                    update_option(self::OPTION_KEY__STRIPE_PUBLIC_KEY, self::encrypt($stripe_public_key, self::ENCRYPT_PASSWORD));
                    // Stripeのシークレットキーをoptionsテーブルに保存
                    update_option(self::OPTION_KEY__STRIPE_SECRET_KEY, self::encrypt($stripe_secret_key, self::ENCRYPT_PASSWORD));
                    // 保存が完了したら、完了メッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__SAVE_INITIAL_CONFIG, "初期設定の保存が完了しました。", self::TRANSIENT_TIME_LIMIT);
                    // (一応)STRIPEの公開キーの不正メッセージをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__INVALID_PUBLIC_KEY);
                    // (一応)STRIPEのシークレットキーの不正メッセージをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__INVALID_SECRET_KEY);
                    // (一応)ユーザが入力したSTRIPEの公開キーをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__TEMP_PUBLIC_KEY);
                    // (一応)ユーザが入力したSTRIPEのシークレットキーをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__TEMP_SECRET_KEY);
                }
                // 有効フラグがFalseの場合(STRIPEの公開キーとシークレットキーが不正の場合)
                else {
                    // ユーザが入力したSTRIPEの公開キーをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_PUBLIC_KEY, $stripe_public_key, self::TRANSIENT_TIME_LIMIT);
                    // ユーザが入力したSTRIPEのシークレットキーをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_SECRET_KEY, $stripe_secret_key, self::TRANSIENT_TIME_LIMIT);
                    // (一応)初期設定の保存完了メッセージを削除
                    delete_transient(self::TRANSIENT_KEY__SAVE_INITIAL_CONFIG);
                }
                // 設定画面にリダイレクト
                wp_safe_redirect(menu_page_url(self::SLUG__INITIAL_CONFIG_FORM, false), 303);
            }
        }
    }

    /**
     * サブメニュー「商品一覧」押下時の画面を表示するcallback関数
     */
    function show_product_list() {
        // OPTIONSから取得した商品情報リストを商品情報リストクラスにセット
        $this->product_list_table->set_product_list(self::get_product_list());
        // 商品情報リストを表示
        echo '<div class="wrap">';
        echo '<h2>商品一覧&nbsp;&nbsp;<a class="button action" href="?page=' . self::SLUG__PRODUCT_EDIT_FORM . '">新規登録</a></h2>';
        $this->product_list_table->prepare_items();
        $page = esc_attr(isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '');
        echo $this->product_list_table->views();
        echo '<form method="get">';
        $this->product_list_table->search_box('検索', 'items');
        printf('<input type="hidden" name="page" value="%s" />', $page);
        $this->product_list_table->display();
        echo '</form>';
        echo '</div>';
    }

    /**
     * 商品情報リストテーブルの左上のアクション押下時の処理
     */
    function act_to_products() {
        // アクション名を取得
        $action = $this->product_list_table->current_action();
        // 対象の商品コードリストを取得
        $product_code_list = $_REQUEST['checked'];
        switch ($action) {
            // 一括削除の場合
            case 'delete':
                // 商品情報の削除処理を実行
                $this->delete_products($product_code_list);
                break;
        }
    }

    /**
     * 商品情報の削除処理
     */
    function delete_products($code_list) {
        // 登録済みの商品情報リストをOPTIONSテーブルから取得
        $product_list = get_option(self::OPTION_KEY__PRODUCT_LIST);
        // 商品情報リストがある場合
        if (!is_null($product_list)) {
            // 商品情報リストをアンシリアライズ
            $product_list = unserialize($product_list);
            // アンシリアライズした商品情報リストが正しく配列の場合
            if (is_array($product_list)) {
                for ($i = 0; $i < count($product_list); $i++) {
                    if ($product_list[$i] instanceof SimpleStripeCheckout_Product) {
                        // 商品コードが一致する場合
                        if (is_array($code_list) && in_array($product_list[$i]->code, $code_list)) {
                            // 削除フラグを立てる
                            unset($product_list[$i]);
                        }
                    }
                }
            }
            //indexを詰める
            $product_list = array_values($product_list);
            // 商品情報リストをシリアライズ
            $product_list = serialize($product_list);
            // 更新した商品情報リストをOPTIONSテーブルにセット
            update_option(self::OPTION_KEY__PRODUCT_LIST, $product_list);
        }
    }

    /**
     * サブメニュー「新規登録」押下時、又は、
     * サブメニュー「商品一覧」より任意の商品の選択時、の画面を表示するcallback関数
     */
    function show_product_edit_form() {
        
        // 商品情報の保存完了メッセージ
        if (false !== ($complete_message = get_transient(self::TRANSIENT_KEY__SAVE_PRODUCT_INFO))) {
            $complete_message = self::getNotice($complete_message, self::NOTICE_TYPE__SUCCESS);
        }
        
        // 商品価格の不正メッセージ
        if (false !== ($invalid_product_price = get_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_PRICE))) {
            $invalid_product_price = self::getNotice($invalid_product_price, self::NOTICE_TYPE__ERROR);
        }
        // 商品提供者名の不正メッセージ
        if (false !== ($invalid_product_provider_name = get_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_PROVIDER_NAME))) {
            $invalid_product_provider_name = self::getNotice($invalid_product_provider_name, self::NOTICE_TYPE__ERROR);
        }
        // 商品名の不正メッセージ
        if (false !== ($invalid_producct_name = get_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_NAME))) {
            $invalid_product_name = self::getNotice($invalid_product_name, self::NOTICE_TYPE__ERROR);
        }
        // 商品通貨の不正メッセージ
        if (false !== ($invalid_product_currency = get_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_CURRENCY))) {
            $invalid_product_currency = self::getNotice($invalid_product_currency, self::NOTICE_TYPE__ERROR);
        }
        // 商品ボタン名の不正メッセージ
        if (false !== ($invalid_product_button_name = get_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_BUTTON_NAME))) {
            $invalid_product_button_name = self::getNotice($invalid_product_button_name, self::NOTICE_TYPE__ERROR);
        }
        
        // 商品コードのパラメータ名
        $param_product_code = self::PARAMETER__PRODUCT_CODE;
        // 商品価格のパラメータ名
        $param_product_price = self::PARAMETER__PRODUCT_PRICE;
        // 商品提供者名のパラメータ名
        $param_product_provider_name = self::PARAMETER__PRODUCT_PROVIDER_NAME;
        // 商品名のパラメータ名
        $param_product_name = self::PARAMETER__PRODUCT_NAME;
        // 商品通貨のパラメータ名
        $param_product_currency = self::PARAMETER__PRODUCT_CURRENCY;
        // 商品ボタン名のパラメータ名
        $param_product_button_name = self::PARAMETER__PRODUCT_BUTTON_NAME;
        // 商品コードをURLクエリー又はTRANSIENTから取得
        if (($product_code = $_REQUEST[self::PARAMETER__PRODUCT_CODE]) || ($product_code = get_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_CODE))) {
            // 商品コードがあればoptionsテーブルから商品情報を取得
            $product_list = get_option(self::OPTION_KEY__PRODUCT_LIST);
            // 商品情報リストがある場合
            if (!is_null($product_list)) {
                // 商品情報リストをアンシリアライズ
                $product_list = unserialize($product_list);
                // アンシリアライズした商品情報リストが正しく配列の場合
                if (is_array($product_list)) {
                    for ($i = 0; $i < count($product_list); $i++) {
                        if ($product_list[$i] instanceof SimpleStripeCheckout_Product) {
                            // 商品コードが一致する場合
                            if ($product_list[$i]->code == $product_code) {
                                $product = $product_list[$i];
                            }
                        }
                    }
                }
            }
        }
        // 商品コードがない場合
        else {
            $product_code_hide_style = 'style="display: none;"';
        }
        
        // 商品価格をTRANSIENTから取得
        if (false === ($product_price = get_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_PRICE))) {
            // 登録済み商品情報がある場合
            if (isset($product) && $product) {
                // 登録済み商品情報の商品価格から取得
                $product_price = $product->price;
            }
        }
        // 商品提供者名をoptionsテーブルから取得
        if (false === ($product_provider_name = get_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_PROVIDER_NAME))) {
            // 登録済み商品情報がある場合
            if (isset($product) && $product) {
                // 登録済み商品情報の商品提供者名から取得
                $product_provider_name = $product->provider_name;
            }
        }
        // 商品名をoptionsテーブルから取得
        if (false === ($product_name = get_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_NAME))) {
            // 登録済み商品情報がある場合
            if (isset($product) && $product) {
                // 登録済み商品情報の商品名から取得
                $product_name = $product->name;
            }
        }
        // 商品通貨をoptionsテーブルから取得
        if (false === ($product_currency = get_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_CURRENCY))) {
            // 登録済み商品情報がある場合
            if (isset($product) && $product) {
                // 登録済み商品情報の商品通貨から取得
                $product_currency = $product->currency;
            }
            // 登録済み商品情報がない場合
            else {
                // デフォルト
                $product_currency = 'JPY';
            }
        }
        // 商品ボタン名をoptionsテーブルから取得
        if (false === ($product_button_name = get_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_BUTTON_NAME))) {
            // 登録済み商品情報がある場合
            if (isset($product) && $product) {
                // 登録済み商品情報の商品ボタン名から取得
                $product_button_name = $product->button_name;
            }
            // 登録済み商品情報がない場合
            else {
                // デフォルト
                $product_button_name = '今すぐ購入'; 
            }
        }
        
        // nonceフィールドを生成・取得
        $nonce_field = wp_nonce_field(self::CREDENTIAL_ACTION__PRODUCT_EDIT, self::CREDENTIAL_NAME__PRODUCT_EDIT, true, false);
        
        // STRIPEの通貨リストのOPTIONタグを生成・取得
        $product_currency_options = self::makeHtmlSelectOptions(self::STRIPE_CURRENCIES, $product_currency, 'label');
        
        // 送信ボタンを生成・取得
        $submit_button = get_submit_button('保存');
        
        // HTMLを出力
        echo <<< EOM
            <div class="wrap">
            <h2>新規登録</h2>
            {$complete_message}
            {$invalid_product_price}
            {$invalid_product_provider_name}
            {$invalid_product_name}
            {$invalid_product_currency}
            {$invalid_product_button_name}
            <form action="" method='post' id="simple-stripe-checkout-product-edit-form">
                {$nonce_field}
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr {$product_code_hide_style}>
                            <th scope="row">この商品のショートコード</th>
                            <td><kbd>[gssc code={$product_code}]</kbd><input type="hidden" name="{$param_product_code}" value="{$product_code}"/></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="{$param_product_price}">価格：</label></th>
                            <td><input type="text" name="{$param_product_price}" value="{$product_price}" class="regular-text" required/></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="{$param_product_provider_name}">提供者：</label></th>
                            <td><input type="text" name="{$param_product_provider_name}" value="{$product_provider_name}" class="regular-text" required/></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="{$param_product_name}">商品名：</label></th>
                            <td><input type="text" name="{$param_product_name}" value="{$product_name}" class="regular-text" required/></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="{$param_product_currency}">通貨：</label></th>
                            <td><select name="{$param_product_currency}" class="postform">{$product_currency_options}</select></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="{$param_product_button_name}">ボタンの名前：</label></th>
                            <td><input type="text" name="{$param_product_button_name}" value="{$product_button_name}" class="regular-text" required/></td>
                        </tr>
                    </tbody>
                </table>
                {$submit_button}
            </form>
            </div>
EOM;
    }

    /**
     * 商品情報を保存するcallback関数
     */
    function save_product() {
        // nonceで設定したcredentialをPOST受信した場合
        if (isset($_POST[self::CREDENTIAL_NAME__PRODUCT_EDIT]) && $_POST[self::CREDENTIAL_NAME__PRODUCT_EDIT]) {
            // nonceで設定したcredentialのチェック結果が問題ない場合
            if (check_admin_referer(self::CREDENTIAL_ACTION__PRODUCT_EDIT, self::CREDENTIAL_NAME__PRODUCT_EDIT)) {
                // 商品コードをPOSTから取得
                $product_code = intval(trim(sanitize_text_field($_POST[self::PARAMETER__PRODUCT_CODE])));
                // 商品価格をPOSTから取得
                $product_price = floatval(trim(sanitize_text_field($_POST[self::PARAMETER__PRODUCT_PRICE])));
                // 商品提供者名をPOSTから取得
                $product_provider_name = trim(sanitize_text_field($_POST[self::PARAMETER__PRODUCT_PROVIDER_NAME]));
                // 商品名をPOSTから取得
                $product_name = trim(sanitize_text_field($_POST[self::PARAMETER__PRODUCT_NAME]));
                // 商品通貨をPOSTから取得
                $product_currency = trim(sanitize_text_field($_POST[self::PARAMETER__PRODUCT_CURRENCY]));
                // 商品ボタン名をPOSTから取得
                $product_button_name = trim(sanitize_text_field($_POST[self::PARAMETER__PRODUCT_BUTTON_NAME]));
                $valid = true;
                // 商品価格が正しくない場合
                if (array_key_exists($product_currency, self::STRIPE_CURRENCIES)) {
                    if ($product_price < self::STRIPE_CURRENCIES[$product_currency]['min']) {
                        // 商品価格の設定し直しを促すメッセージをTRANSIENTに5秒間保持
                        set_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_PRICE, "商品価格が正しくありません。", self::TRANSIENT_TIME_LIMIT);
                        // 有効フラグをFalse
                        $valid = false;
                    }
                }
                // 商品提供者名が正しくない場合
                if (strlen($product_provider_name) === 0) {
                    // 商品提供者名の設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_PROVIDER_NAME, "商品提供者名が正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 商品名が正しくない場合
                if (strlen($product_name) === 0) {
                    // 商品名の設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_NAME, "商品名が正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 商品通貨が正しくない場合
                if (!array_key_exists($product_currency, self::STRIPE_CURRENCIES)) {
                    // 商品通貨の設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_CURRENCY, "商品通貨が正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 商品ボタン名が正しくない場合
                if (strlen($product_button_name) === 0) {
                    // 商品ボタン名の設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_BUTTON_NAME, "商品ボタン名が正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 有効フラグがTrueの場合(商品情報が正しい場合)
                if ($valid) {
                    // 保存処理
                    
                    // 登録済みの商品情報リストをoptionsテーブルから取得
                    $product_list = get_option(self::OPTION_KEY__PRODUCT_LIST);
                    // 登録済みの商品情報リストが0件の場合
                    if (is_null($product_list)) {
                        // 商品情報リストを初期化
                        $product_list = array();
                    }
                    // 登録済みの商品情報リストが1件以上ある場合
                    else {
                        // シリアライズされている商品情報リストをアンシリアライズ
                        $product_list = unserialize($product_list);
                    }
                    // 更新の場合(商品コードがある場合)
                    if ($product_code > 0) {
                        // 有効フラグを一旦FALSEにする
                        // 以下で更新対象があればTRUEに、無ければFALSEのまま不正時処理に進む
                        $valid = false;
                        for ($i = 0; $i < count($product_list); $i++) {
                            if ($product_list[$i] instanceof SimpleStripeCheckout_Product) {
                                // 商品コードが一致する場合(更新対象の商品情報の場合)
                                if ($product_list[$i]->code == $product_code) {
                                    // 商品情報の各値をセット
                                    $product_list[$i]->price         = $product_price;
                                    $product_list[$i]->provider_name = $product_provider_name;
                                    $product_list[$i]->name          = $product_name;
                                    $product_list[$i]->currency      = $product_currency;
                                    $product_list[$i]->button_name   = $product_button_name;
                                    // 更新対象があったので有効フラグをTRUEに戻す
                                    $valid = true;
                                    break;
                                }
                            }
                        }
                    }
                    // 新規の場合(商品コードがない場合)
                    else {
                        // 最も大きい商品コードを取得
                        $max_product_code = 0;
                        for ($i = 0; $i < count($product_list); $i++) {
                            if ($product_list[$i] instanceof SimpleStripeCheckout_Product) {
                                if ($product_list[$i]->code > $max_product_code) {
                                    $max_product_code = $product_list[$i]->code;
                                }
                            }
                        }
                        // 商品情報の各値をセット
                        $product = new SimpleStripeCheckout_Product();
                        $product->code          = $max_product_code + 1;
                        $product->price         = $product_price;
                        $product->provider_name = $product_provider_name;
                        $product->name          = $product_name;
                        $product->currency      = $product_currency;
                        $product->button_name   = $product_button_name;
                        // 商品情報リストに追加
                        $product_list[] = $product;
                    }
                    // 有効フラグがTrueの場合(商品情報の保存が完了した場合)
                    if ($valid) {
                        // 商品情報リストをシリアライズしてoptionsテーブルに保存
                        update_option(self::OPTION_KEY__PRODUCT_LIST, serialize($product_list));
                        
                        // 保存完了メッセージをTRANSIENTに5秒間保持
                        set_transient(self::TRANSIENT_KEY__SAVE_PRODUCT_INFO, "商品情報の保存が完了しました。", self::TRANSIENT_TIME_LIMIT);
                        
                        // (一応)商品価格の不正メッセージをTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_PRICE);
                        // (一応)商品提供者名の不正メッセージをTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_PROVIDER_NAME);
                        // (一応)商品名の不正メッセージをTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_NAME);
                        // (一応)商品通貨の不正メッセージをTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_CURRENCY);
                        // (一応)商品ボタン名の不正メッセージをTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__INVALID_PRODUCT_BUTTON_NAME);
                        
                        // (一応)ユーザが入力した商品価格をTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_PRICE);
                        // (一応)ユーザが入力した商品提供者名をTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_PROVIDER_NAME);
                        // (一応)ユーザが入力した商品名をTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_NAME);
                        // (一応)ユーザが入力した商品通貨をTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_CURRENCY);
                        // (一応)ユーザが入力した商品ボタン名をTRANSIENTから削除
                        delete_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_BUTTON_NAME);
                    }
                }
                // 有効フラグがFalseの場合(商品情報が不正の場合)
                if (!$valid) {
                    // ユーザが入力した商品価格をTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_PRICE, $product_price, self::TRANSIENT_TIME_LIMIT);
                    // ユーザが入力した商品提供者名をTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_PROVIDER_NAME, $product_provider_name, self::TRANSIENT_TIME_LIMIT);
                    // ユーザが入力した商品名をTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_NAME, $product_name, self::TRANSIENT_TIME_LIMIT);
                    // ユーザが入力した商品通貨をTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_CURRENCY, $product_currency, self::TRANSIENT_TIME_LIMIT);
                    // ユーザが入力した商品ボタン名をTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_PRODUCT_BUTTON_NAME, $product_button_name, self::TRANSIENT_TIME_LIMIT);

                    // (一応)商品情報の保存完了メッセージを削除
                    delete_transient(self::TRANSIENT_KEY__SAVE_PRODUCT_INFO);
                }
                // 設定画面にリダイレクト
                wp_safe_redirect(menu_page_url(self::SLUG__PRODUCT_EDIT_FORM, false), 303);
            }
        }
    }

    /**
     * サブメニュー「メール設定」押下時の画面を表示するcallback関数
     */
    function show_mail_config_form() {
        // メール設定の保存完了メッセージ
        if (false !== ($complete_message = get_transient(self::TRANSIENT_KEY__SAVE_MAIL_CONFIG))) {
            $complete_message = self::getNotice($complete_message, self::NOTICE_TYPE__SUCCESS);
        }
        // 販売者向け受信メルアドの不正メッセージ
        if (false !== ($invalid_seller_receive_address = get_transient(self::TRANSIENT_KEY__INVALID_SELLER_RECEIVE_ADDRESS))) {
            $invalid_seller_receive_address = self::getNotice($invalid_seller_receive_address, self::NOTICE_TYPE__ERROR);
        }
        // 販売者向け送信元メルアドの不正メッセージ
        if (false !== ($invalid_seller_from_address = get_transient(self::TRANSIENT_KEY__INVALID_SELLER_FROM_ADDRESS))) {
            $invalid_seller_from_address = self::getNotice($invalid_seller_from_address, self::NOTICE_TYPE__ERROR);
        }
        // 購入者向け送信元メルアドの不正メッセージ
        if (false !== ($invalid_buyer_from_address = get_transient(self::TRANSIENT_KEY__INVALID_BUYER_FROM_ADDRESS))) {
            $invalid_buyer_from_address = self::getNotice($invalid_buyer_from_address, self::NOTICE_TYPE__ERROR);
        }
        // 即時決済フラグの不正メッセージ
        if (false !== ($invalid_immediate_settlement = get_transient(self::TRANSIENT_KEY__INVALID_IMMEDIATE_SETTLEMENT))) {
            $invalid_immediate_settlement = self::getNotice($invalid_immediate_settlement, self::NOTICE_TYPE__ERROR);
        }
        // 販売者向け受信メルアドのパラメータ名
        $param_seller_receive_address = self::PARAMETER__SELLER_RECEIVE_ADDRESS;
        // 販売者向け送信元メルアドのパラメータ名
        $param_seller_from_address = self::PARAMETER__SELLER_FROM_ADDRESS;
        // 購入者向け送信元メルアドのパラメータ名
        $param_buyer_from_address = self::PARAMETER__BUYER_FROM_ADDRESS;
        // 即時決済フラグのパラメータ名
        $param_immediate_settlement = self::PARAMETER__IMMEDIATE_SETTLEMENT;
        // 販売者向け受信メルアドをTRANSIENTから取得
        if (false === ($seller_receive_address = get_transient(self::TRANSIENT_KEY__TEMP_SELLER_RECEIVE_ADDRESS))) {
            // 無ければoptionsテーブルから取得
            $seller_receive_address = get_option(self::OPTION_KEY__SELLER_RECEIVE_ADDRESS);
        }
        // 販売者向け送信元メルアドをTRANSIENTから取得
        if (false === ($seller_from_address = get_transient(self::TRANSIENT_KEY__TEMP_SELLER_FROM_ADDRESS))) {
            // 無ければoptionsテーブルから取得
            $seller_from_address = get_option(self::OPTION_KEY__SELLER_FROM_ADDRESS);
        }
        // 購入者向け送信元メルアドをTRANSIENTから取得
        if (false === ($buyer_from_address = get_transient(self::TRANSIENT_KEY__TEMP_BUYER_FROM_ADDRESS))) {
            // 無ければoptionsテーブルから取得
            $buyer_from_address = get_option(self::OPTION_KEY__BUYER_FROM_ADDRESS);
        }
        // 即時決済フラグをTRANSIENTから取得
        if (false === ($immediate_settlement = get_transient(self::TRANSIENT_KEY__TEMP_IMMEDIATE_SETTLEMENT))) {
            // 無ければoptionsテーブルから取得
            $immediate_settlement = get_option(self::OPTION_KEY__IMMEDIATE_SETTLEMENT);
        }
        if ($immediate_settlement === 'ON') {
            $checked_immediate_settlement_on = 'checked="checked"';
        }
        if ($immediate_settlement === 'OFF') {
            $checked_immediate_settlement_off = 'checked="checked"';
        }
        // nonceフィールドを生成・取得
        $nonce_field = wp_nonce_field(self::CREDENTIAL_ACTION__MAIL_CONFIG, self::CREDENTIAL_NAME__MAIL_CONFIG, true, false);
        // 送信ボタンを生成・取得
        $submit_button = get_submit_button('保存');
        // HTMLを出力
        echo <<< EOM
            <div class="wrap">
            <h2>メール設定</h2>
            {$complete_message}
            {$invalid_seller_receive_address}
            {$invalid_seller_from_address}
            {$invalid_buyer_from_address}
            {$invalid_immediate_settlement}
            <form action="" method='post' id="simple-stripe-checkout-initial-config-form">
                {$nonce_field}
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row" colspan=2><h3>▼販売者向けメール設定</h3></th>
                        </tr>
                        <tr>
                            <th scope="row"><label for="{$param_seller_receive_address}">&nbsp;&nbsp;&nbsp;&nbsp;受信メールアドレス：</label></th>
                            <td>
                                <input type="text" name="{$param_seller_receive_address}" value="{$seller_receive_address}" class="regular-text"/>
                                <p class="description" id="tagline-description">※複数ある場合はカンマで区切ってください。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="{$param_seller_from_address}">&nbsp;&nbsp;&nbsp;&nbsp;送信元メールアドレス：</label></th>
                            <td>
                                <input type="text" name="{$param_seller_from_address}" value="{$seller_from_address}" class="regular-text"/>
                                <!--p class="description" id="tagline-description">※複数ある場合はカンマで区切ってください。</p-->
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" colspan=2><h3>▼購入者向けメール設定</h3></th>
                        </tr>
                        <tr>
                            <th scope="row"><label for="{$param_buyer_from_address}">&nbsp;&nbsp;&nbsp;&nbsp;送信元メールアドレス：</label></th>
                            <td>
                                <input type="text" name="{$param_buyer_from_address}" value="{$buyer_from_address}" class="regular-text"/>
                                <!--p class="description" id="tagline-description">※複数ある場合はカンマで区切ってください。</p-->
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" colspan=2>
                                <h3>▼即時決済をしますか？</h3>
                                <div style="padding-left: 21px;">
                                    <fieldset>
                                        <legend class="screen-reader-text"><span>即時決済をしますか？</span></legend>
                                        <label>　<input type="radio" name="{$param_immediate_settlement}" value="ON" {$checked_immediate_settlement_on}> <span class="date-time-text format-i18n">ON</span></label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <label>　<input type="radio" name="{$param_immediate_settlement}" value="OFF" {$checked_immediate_settlement_off}> <span class="date-time-text format-i18n">OFF</span><p style="margin-top: 12px;" class="description" id="tagline-description">※OFFの場合は、24時間後に決済確定となるメールが配信されますが、実際は、決済確定の為のリンクが送られ、手動で確定する必要があります。迷惑メールなどでメールが届かない場合は、定期的にStripe管理画面での確認が必要です。</p></label><br>
                                    </fieldset>
                                </div>
                            </th>
                        </tr>
                    </tbody>
                </table>
                {$submit_button}
            </form>
            </div>
EOM;
    }

    /**
     * メール設定を保存するcallback関数
     */
    function save_mail_config() {
        // nonceで設定したcredentialをPOST受信した場合
        if (isset($_POST[self::CREDENTIAL_NAME__MAIL_CONFIG]) && $_POST[self::CREDENTIAL_NAME__MAIL_CONFIG]) {
            // nonceで設定したcredentialのチェック結果が問題ない場合
            if (check_admin_referer(self::CREDENTIAL_ACTION__MAIL_CONFIG, self::CREDENTIAL_NAME__MAIL_CONFIG)) {
                // 販売者向け受信メルアドをPOSTから取得
                $seller_receive_address = trim(sanitize_text_field($_POST[self::PARAMETER__SELLER_RECEIVE_ADDRESS]));
                // 販売者向け送信元メルアドをPOSTから取得
                $seller_from_address = trim(sanitize_text_field($_POST[self::PARAMETER__SELLER_FROM_ADDRESS]));
                // 購入者向け送信元メルアドをPOSTから取得
                $buyer_from_address = trim(sanitize_text_field($_POST[self::PARAMETER__BUYER_FROM_ADDRESS]));
                // 即時決済フラグをPOSTから取得
                $immediate_settlement = trim(sanitize_text_field($_POST[self::PARAMETER__IMMEDIATE_SETTLEMENT]));
                $valid = true;
                // 販売者向け受信メルアドが正しくない場合
                if (!preg_match(self::REGEXP_MULTIPLE_ADDRESS, $seller_receive_address)) {
                    // 販売者向け受信メルアドの設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_SELLER_RECEIVE_ADDRESS, "販売者向け受信メールアドレスが正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 販売者向け送信元メルアドが正しくない場合
                if (!preg_match(self::REGEXP_SINGLE_ADDRESS, $seller_from_address)) {
                    // 販売者向け送信元メルアドの設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_SELLER_FROM_ADDRESS, "販売者向け送信元メルアドが正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 購入者向け送信元メルアドが正しくない場合
                if (!preg_match(self::REGEXP_SINGLE_ADDRESS, $buyer_from_address)) {
                    // 購入者向け送信元メルアドの設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_BUYER_FROM_ADDRESS, "購入者向け送信元メルアドが正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 即時決済フラグが正しくない場合
                if ($immediate_settlement !== 'ON' && $immediate_settlement !== 'OFF') {
                    // 即時決済フラグの設定し直しを促すメッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__INVALID_IMMEDIATE_SETTLEMENT, "即時決済のON/OFFが正しくありません。", self::TRANSIENT_TIME_LIMIT);
                    // 有効フラグをFalse
                    $valid = false;
                }
                // 有効フラグがTrueの場合(各メルアド、即時決済フラグが正しい場合)
                if ($valid) {
                    // 保存処理
                    // 販売者向け受信メルアドをoptionsテーブルに保存
                    update_option(self::OPTION_KEY__SELLER_RECEIVE_ADDRESS, $seller_receive_address);
                    // 販売者向け受信メルアドをoptionsテーブルに保存
                    update_option(self::OPTION_KEY__SELLER_FROM_ADDRESS, $seller_from_address);
                    // 購入者向け送信元メルアドをoptionsテーブルに保存
                    update_option(self::OPTION_KEY__BUYER_FROM_ADDRESS, $buyer_from_address);
                    // 即時決済フラグをoptionsテーブルに保存
                    update_option(self::OPTION_KEY__IMMEDIATE_SETTLEMENT, $immediate_settlement);
                    // 保存が完了したら、完了メッセージをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__SAVE_MAIL_CONFIG, "メール設定の保存が完了しました。", self::TRANSIENT_TIME_LIMIT);
                    // (一応)販売者向け受信メルアドの不正メッセージをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__INVALID_SELLER_RECEIVE_ADDRESS);
                    // (一応)販売者向け送信元メルアドの不正メッセージをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__INVALID_SELLER_FROM_ADDRESS);
                    // (一応)購入者向け送信元メルアドの不正メッセージをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__INVALID_BUYER_FROM_ADDRESS);
                    // (一応)即時決済フラグの不正メッセージをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__INVALID_IMMEDIATE_SETTLEMENT);
                    // (一応)ユーザが入力した販売者向け受信メルアドをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__TEMP_SELLER_RECEIVE_ADDRESS);
                    // (一応)ユーザが入力した販売者向け送信元メルアドをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__TEMP_SELLER_FROM_ADDRESS);
                    // (一応)ユーザが入力した購入者向け送信元メルアドをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__TEMP_BUYER_FROM_ADDRESS);
                    // (一応)ユーザが入力した即時決済フラグをTRANSIENTから削除
                    delete_transient(self::TRANSIENT_KEY__TEMP_IMMEDIATE_SETTLEMENT);
                }
                // 有効フラグがFalseの場合(何れかのメルアドが不正の場合)
                else {
                    // ユーザが入力した販売者向け受信メルアドをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_SELLER_RECEIVE_ADDRESS, $seller_receive_address, self::TRANSIENT_TIME_LIMIT);
                    // ユーザが入力した販売者向け送信元メルアドをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_SELLER_FROM_ADDRESS, $seller_from_address, self::TRANSIENT_TIME_LIMIT);
                    // ユーザが入力した購入者向け送信元メルアドをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_BUYER_FROM_ADDRESS, $buyer_from_address, self::TRANSIENT_TIME_LIMIT);
                    // ユーザが入力した即時決済フラグをTRANSIENTに5秒間保持
                    set_transient(self::TRANSIENT_KEY__TEMP_IMMEDIATE_SETTLEMENT, $immediate_settlement, self::TRANSIENT_TIME_LIMIT);
                    // (一応)メール設定の保存完了メッセージを削除
                    delete_transient(self::TRANSIENT_KEY__SAVE_MAIL_CONFIG);
                }
                // 設定画面にリダイレクト
                wp_safe_redirect(menu_page_url(self::SLUG__MAIL_CONFIG_FORM, false), 303);
            }
        }
    }

} // end of class


?>