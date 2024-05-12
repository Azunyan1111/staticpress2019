<?php

use static_press\includes\Static_Press;
use static_press\includes\Static_Press_Admin;

if (!class_exists('StaticPress_CLI_Command')) {
    class StaticPress_CLI_Command {

        /**
         * StaticPressの初期化をトリガーする
         *
         * ## EXAMPLES
         *
         *     wp staticpress2019 build
         *
         */
        public function build($args, $assoc_args) {
            $staticpress = new Static_Press(
                Static_Press_Admin::static_url(),
                Static_Press_Admin::static_dir(),
                Static_Press_Admin::remote_get_option()
            );

            // Register hooks and filters as needed for CLI context
            add_filter('StaticPress::get_url', array($staticpress, 'replace_url'));
            add_filter('StaticPress::put_content', array($staticpress, 'rewrite_generator_tag'), 10, 2);
            add_filter('StaticPress::put_content', array($staticpress, 'add_last_modified'), 10, 2);
            add_filter('StaticPress::put_content', array($staticpress, 'remove_link_tag'), 10, 2);
            add_filter('StaticPress::put_content', array($staticpress, 'replace_relative_uri'), 10, 2);
            add_filter('https_local_ssl_verify', '__return_false');

            try {
                $result = $staticpress->ajax_init_cmd();
                // {"result":true,"urls_count":[{"type":"content_file","count":"59"},{"type":"front_page","count":"1"},{"type":"seo_files","count":"2"},{"type":"static_file","count":"1547"}]}
                if (isset($result['result']) && $result['result'] === false) {
                    WP_CLI::error("Error during static site generation: " . $result['message']);
                }
                for ($i = 0; $i < 600; $i++) { // タイムアウトは10分
                    sleep(1);
                    $result = $staticpress->ajax_fetch_cmd();
                    if (isset($result['final']) && $result['final'] === true) {
                        break;
                    };
                }
                $result = $staticpress->ajax_finalyze_cmd();
                $resp = json_encode( $result );
                WP_CLI::success("Resp:" . $resp);
                WP_CLI::success("Static site generation initiated.");
            } catch (Exception $e) {
                WP_CLI::error("Error during static site generation: " . $e->getMessage());
            }
        }

        /**
         * StaticPressの設定オプションを更新する
         *
         * ## OPTIONS
         *
         * <static_url>
         * : 静的ファイルのURL
         *
         * <static_dir>
         * : 静的ファイルのディレクトリパス
         *
         * ## EXAMPLES
         *
         *     wp staticpress2019 option http://localhost:1111/blog/ /var/www/html/wp-content/uploads/staticpress2019/
         *
         */
        public function option($args, $assoc_args) {
            list($static_url, $static_dir) = $args;

            // オプションを更新する
            update_option('StaticPress::static url', $static_url);
            update_option('StaticPress::static dir', $static_dir);

            WP_CLI::success("StaticPress options updated successfully.");
        }
    }
}
