<?php
/**
 * TA_Email — WordPress email service wrapper.
 * Uses wp_mail() with HTML support.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Email {

    /** Set HTML content type for all outgoing mails */
    public static function send( $to, $subject, $html, $attachments = array() ) {
        add_filter( 'wp_mail_content_type', array( __CLASS__, 'set_html_content_type' ) );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: TickerAdda <noreply@tickeradda.in>',
        );

        $sent = wp_mail( $to, $subject, $html, $headers, $attachments );

        remove_filter( 'wp_mail_content_type', array( __CLASS__, 'set_html_content_type' ) );

        return $sent;
    }

    public static function set_html_content_type() {
        return 'text/html';
    }

    // ── OTP Email ─────────────────────────────────────────────────────────────
    public static function send_otp( $to, $otp, $type = 'signup' ) {
        $purpose = $type === 'reset' ? 'Password Reset' : 'Email Verification';
        $subject = "{$purpose} OTP - TickerAdda";
        $html = self::base_template( $subject, "
            <p style='font-size:16px;'>Your <strong>{$purpose}</strong> OTP is:</p>
            <div style='font-size:36px;font-weight:bold;letter-spacing:12px;
                        background:#f0f0fa;padding:20px;border-radius:8px;
                        text-align:center;color:#4f46e5;margin:20px 0;'>
                {$otp}
            </div>
            <p style='color:#666;font-size:14px;'>This OTP expires in <strong>10 minutes</strong>. 
            Do not share it with anyone.</p>
        " );
        return self::send( $to, $subject, $html );
    }

    // ── Ticket Status Email ───────────────────────────────────────────────────
    public static function send_ticket_status( $to, $seller_name, $event_name, $status ) {
        $is_approved = $status === 'approved';
        $subject     = $is_approved
            ? "✅ Ticket Approved: {$event_name}"
            : "❌ Ticket Rejected: {$event_name}";
        $body = $is_approved
            ? "<p>Hi {$seller_name},</p>
               <p>Great news! Your ticket for <strong>{$event_name}</strong> has been <span style='color:#16a34a;'>approved</span> and is now live on the marketplace.</p>
               <p>Good luck with your sale!</p>"
            : "<p>Hi {$seller_name},</p>
               <p>Unfortunately, your ticket for <strong>{$event_name}</strong> has been <span style='color:#dc2626;'>rejected</span> by our admin team.</p>
               <p>Please review our seller guidelines and re-submit with a valid ticket proof.</p>";

        $html = self::base_template( $subject, $body );
        return self::send( $to, $subject, $html );
    }

    // ── Order Confirmation to Buyer ───────────────────────────────────────────
    public static function send_order_confirmation( $buyer_email, $buyer_name, $order, $ticket, $attachments = array() ) {
        $order_id    = '#' . str_pad( $order->id, 6, '0', STR_PAD_LEFT );
        $subject     = "🎟️ Order Confirmed: {$ticket->event_name}";
        $amount      = '₹' . number_format( $order->total_amount, 2 );
        $event_date  = date( 'd M Y', strtotime( $ticket->event_date ) );

        $html = self::base_template( $subject, "
            <p>Hi {$buyer_name},</p>
            <p>Your purchase is <strong style='color:#16a34a;'>confirmed!</strong></p>
            <div style='background:#f9fafb;border-radius:8px;padding:20px;margin:20px 0;border:1px solid #e5e7eb;'>
                <p><strong>Event:</strong> " . esc_html( $ticket->event_name ) . "</p>
                <p><strong>Date:</strong> {$event_date} at {$ticket->event_time}</p>
                <p><strong>Quantity:</strong> {$order->quantity}</p>
                <p><strong>Amount Paid:</strong> {$amount}</p>
                <p><strong>Order ID:</strong> {$order_id}</p>
            </div>
            <p style='background:#fffbeb; padding:15px; border-radius:8px; border:1px solid #fde68a; color:#92400e;'>
                <strong>Note:</strong> We are currently verifying your payment. Your ticket will be emailed to you and available in your dashboard once the verification is complete (usually within 15-30 minutes).
            </p>
            <p>Your invoice is attached to this email.</p>
        " );

        return self::send( $buyer_email, $subject, $html, $attachments );
    }

    // ── Final Ticket Delivery ────────────────────────────────────────────────
    public static function send_ticket_delivery( $buyer_email, $buyer_name, $order, $ticket, $attachments = array() ) {
        $order_id = '#' . str_pad( $order->id, 6, '0', STR_PAD_LEFT );
        $subject  = "🎫 Your Ticket is Ready: {$ticket->event_name}";
        
        $html = self::base_template( $subject, "
            <p>Hi {$buyer_name},</p>
            <p>Your payment has been verified! Your ticket for <strong>" . esc_html( $ticket->event_name ) . "</strong> is now ready.</p>
            <div style='background:#f0fdf4;border-radius:8px;padding:20px;margin:20px 0;border:1px solid #bbf7d0;'>
                <p><strong>Event:</strong> " . esc_html( $ticket->event_name ) . "</p>
                <p><strong>Order ID:</strong> {$order_id}</p>
            </div>
            <p>The ticket file is attached to this email. You can also view it in your <a href='" . home_url('/buyer-dashboard') . "' style='color:#4f46e5;'>Buyer Dashboard</a>.</p>
            <p>Enjoy the event!</p>
        " );

        return self::send( $buyer_email, $subject, $html, $attachments );
    }

    // ── Ticket Sold — Notify Seller ───────────────────────────────────────────
    public static function send_ticket_sold( $seller_email, $seller_name, $buyer_name, $buyer_email, $buyer_phone, $ticket, $order ) {
        $subject = "🎉 Ticket Sold: {$ticket->event_name}";
        $amount  = '₹' . number_format( $order->total_amount, 2 );

        $html = self::base_template( $subject, "
            <p>Hi {$seller_name},</p>
            <p>Your ticket for <strong>" . esc_html( $ticket->event_name ) . "</strong> has been sold!</p>
            <div style='background:#f9fafb;border-radius:8px;padding:20px;margin:20px 0;border:1px solid #e5e7eb;'>
                <p><strong>Buyer Name:</strong> {$buyer_name}</p>
                <p><strong>Buyer Email:</strong> {$buyer_email}</p>
                <p><strong>Buyer Phone:</strong> {$buyer_phone}</p>
                <p><strong>Sale Amount:</strong> {$amount}</p>
            </div>
            <p>The payment will be processed to your account. Thank you for using TickerAdda!</p>
        " );

        return self::send( $seller_email, $subject, $html );
    }

    // ── Buyer Is Interested — Notify Seller ───────────────────────────────────
    public static function send_buyer_interested( $seller_email, $seller_name, $buyer_name, $buyer_email, $buyer_phone, $ticket, $order_id ) {
        $subject = "📢 New Purchase Request: {$ticket->event_name}";
        $amount  = '₹' . number_format( $ticket->price, 2 );

        $html = self::base_template( $subject, "
            <p>Hi {$seller_name},</p>
            <p>Great news! A buyer is interested in purchasing your ticket for <strong>" . esc_html( $ticket->event_name ) . "</strong>.</p>
            <div style='background:#f9fafb;border-radius:8px;padding:20px;margin:20px 0;border:1px solid #e5e7eb;'>
                <p><strong>Buyer Name:</strong> {$buyer_name}</p>
                <p><strong>Buyer Email:</strong> {$buyer_email}</p>
                <p><strong>Buyer Phone:</strong> {$buyer_phone}</p>
                <p><strong>Ticket Price:</strong> {$amount}</p>
            </div>
            <p>Please log in to your <a href='" . home_url('/seller-dashboard') . "' style='color:#4f46e5;'>Seller Dashboard</a> to confirm the sale and contact the buyer to arrange payment/transfer.</p>
        " );

        return self::send( $seller_email, $subject, $html );
    }

    // ── Purchase Request Submitted — Notify Buyer ─────────────────────────────
    public static function send_purchase_request_confirmation( $buyer_email, $buyer_name, $seller_name, $seller_email, $seller_phone, $ticket, $order_id ) {
        $subject = "📨 Request Submitted: {$ticket->event_name}";
        $amount  = '₹' . number_format( $ticket->price, 2 );

        $html = self::base_template( $subject, "
            <p>Hi {$buyer_name},</p>
            <p>Your request to purchase the ticket for <strong>" . esc_html( $ticket->event_name ) . "</strong> has been submitted!</p>
            <div style='background:#f0f9ff;border-radius:8px;padding:20px;margin:20px 0;border:1px solid #bae6fd;'>
                <p><strong>Ticket Price:</strong> {$amount}</p>
                <h4 style='margin-bottom:5px;'>Seller Contact Details:</h4>
                <p style='margin:0;'><strong>Name:</strong> {$seller_name}</p>
                <p style='margin:0;'><strong>Email:</strong> {$seller_email}</p>
                <p style='margin:0;'><strong>Phone:</strong> {$seller_phone}</p>
            </div>
            <p>You can contact the seller directly via the details above or wait for them to reach out to you to complete the payment and transfer.</p>
            <p>View your request status in your <a href='" . home_url('/buyer-dashboard-2') . "' style='color:#4f46e5;'>Buyer Dashboard</a>.</p>
        " );

        return self::send( $buyer_email, $subject, $html );
    }

    // ── Sale Confirmed — Notify Buyer ─────────────────────────────────────────
    public static function send_sale_confirmed( $buyer_email, $buyer_name, $seller_name, $seller_email, $seller_phone, $ticket, $order ) {
        $order_id_s = '#' . str_pad( $order->id, 6, '0', STR_PAD_LEFT );
        $subject = "✅ Purchase Confirmed: {$ticket->event_name}";
        $amount  = '₹' . number_format( $order->total_amount, 2 );

        $html = self::base_template( $subject, "
            <p>Hi {$buyer_name},</p>
            <p>The seller has <strong style='color:#16a34a;'>confirmed</strong> your purchase request for <strong>" . esc_html( $ticket->event_name ) . "</strong>!</p>
            <div style='background:#f0fdf4;border-radius:8px;padding:20px;margin:20px 0;border:1px solid #bbf7d0;'>
                <p><strong>Order ID:</strong> {$order_id_s}</p>
                <p><strong>Amount to Pay:</strong> {$amount}</p>
                <h4 style='margin-bottom:5px;'>Seller Contact Details:</h4>
                <p style='margin:0;'><strong>Name:</strong> {$seller_name}</p>
                <p style='margin:0;'><strong>Email:</strong> {$seller_email}</p>
                <p style='margin:0;'><strong>Phone:</strong> {$seller_phone}</p>
            </div>
            <p>Please contact the seller to arrange the payment and ticket transfer.</p>
            <p>Thank you for using TickerAdda!</p>
        " );

        return self::send( $buyer_email, $subject, $html );
    }

    // ── KYC Status Email ──────────────────────────────────────────────────────
    public static function send_kyc_status( $to, $name, $status, $reason = '' ) {
        $is_approved = $status === 'approved';
        $subject     = $is_approved
            ? '✅ KYC Approved - TickerAdda'
            : '❌ KYC Rejected - TickerAdda';
        $body = $is_approved
            ? "<p>Hi {$name},</p><p>Your KYC has been <strong style='color:#16a34a;'>approved</strong>! You can now list tickets on TickerAdda.</p>"
            : "<p>Hi {$name},</p><p>Your KYC submission was <strong style='color:#dc2626;'>rejected</strong>.</p><p><strong>Reason:</strong> " . esc_html( $reason ) . "</p><p>Please re-submit with correct documents.</p>";

        return self::send( $to, $subject, self::base_template( $subject, $body ) );
    }

    // ── Base HTML Email Template ──────────────────────────────────────────────
    private static function base_template( $title, $body ) {
        $year    = date( 'Y' );
        $site    = get_bloginfo( 'name' );
        $logo    = get_template_directory_uri() . '/assets/images/logo.png';

        return "<!DOCTYPE html>
<html><head>
<meta charset='UTF-8'>
<style>
  body{font-family:Arial,sans-serif;background:#f3f4f6;margin:0;padding:0;}
  .wrap{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);}
  .header{background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:30px;text-align:center;}
  .header h1{color:#fff;margin:0;font-size:22px;letter-spacing:1px;}
  .body{padding:30px;color:#333;line-height:1.6;}
  .footer{background:#f9fafb;padding:20px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;}
  a{color:#4f46e5;}
</style>
</head>
<body>
<div class='wrap'>
  <div class='header'><h1>TickerAdda</h1></div>
  <div class='body'>{$body}</div>
  <div class='footer'>&copy; {$year} {$site}. All rights reserved. | <a href='" . home_url() . "'>Visit Website</a></div>
</div>
</body></html>";
    }
}
