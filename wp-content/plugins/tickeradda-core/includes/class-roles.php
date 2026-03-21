<?php
/**
 * TA_Roles — Registers custom WordPress user roles on activation.
 * Removed on plugin deactivation only if explicitly desired (not done by default
 * so existing users keep their roles after plugin updates).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Roles {

    public static function register() {
        // ── Buyer Role ────────────────────────────────────────────────────────
        add_role( 'ta_buyer', 'TickerAdda Buyer', array(
            'read'             => true,
            'ta_buy_tickets'   => true,
            'ta_view_orders'   => true,
        ) );

        // ── Seller Role ───────────────────────────────────────────────────────
        add_role( 'ta_seller', 'TickerAdda Seller', array(
            'read'             => true,
            'ta_sell_tickets'  => true,
            'ta_view_orders'   => true,
            'ta_submit_kyc'    => true,
        ) );

        // ── Both (Seller + Buyer) Role ────────────────────────────────────────
        add_role( 'ta_both', 'TickerAdda Seller & Buyer', array(
            'read'             => true,
            'ta_buy_tickets'   => true,
            'ta_sell_tickets'  => true,
            'ta_view_orders'   => true,
            'ta_submit_kyc'    => true,
        ) );

        // ── Grant admin all custom caps ────────────────────────────────────────
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'ta_buy_tickets' );
            $admin->add_cap( 'ta_sell_tickets' );
            $admin->add_cap( 'ta_view_orders' );
            $admin->add_cap( 'ta_submit_kyc' );
            $admin->add_cap( 'ta_manage_tickets' );
            $admin->add_cap( 'ta_manage_orders' );
            $admin->add_cap( 'ta_manage_kyc' );
            $admin->add_cap( 'ta_manage_users' );
        }
    }

    /**
     * Check if a WP_User has a given TickerAdda role.
     * Supports 'admin', 'seller', 'buyer', or 'both' (seller+buyer).
     */
    public static function user_has_role( $user, $role ) {
        if ( ! $user || ! ( $user instanceof WP_User ) ) {
            return false;
        }
        switch ( $role ) {
            case 'admin':
                return in_array( 'administrator', (array) $user->roles, true );
            case 'both':
                return in_array( 'ta_both', (array) $user->roles, true )
                    || in_array( 'administrator', (array) $user->roles, true );
            case 'seller':
                return in_array( 'ta_seller', (array) $user->roles, true )
                    || in_array( 'ta_both', (array) $user->roles, true )
                    || in_array( 'administrator', (array) $user->roles, true );
            case 'buyer':
                return in_array( 'ta_buyer', (array) $user->roles, true )
                    || in_array( 'ta_seller', (array) $user->roles, true )
                    || in_array( 'ta_both', (array) $user->roles, true )
                    || in_array( 'administrator', (array) $user->roles, true );
            default:
                return false;
        }
    }
}
