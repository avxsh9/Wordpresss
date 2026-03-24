<?php
/**
 * Migration: Set all ta_buyer and ta_seller users to ta_both.
 */
require_once( 'wp-load.php' );

$users = get_users( array(
    'role__in' => array( 'ta_buyer', 'ta_seller' )
) );

$count = 0;
foreach ( $users as $user ) {
    $user->set_role( 'ta_both' );
    update_user_meta( $user->ID, 'ta_role_label', 'both' );
    $count++;
}

echo "Sucessfully migrated {$count} users to 'ta_both' role.";
