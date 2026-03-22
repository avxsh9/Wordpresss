<?php
/**
 * TA_PDF_Invoice — Generates a simple invoice PDF using pure PHP (no Composer).
 * Outputs styled HTML converted to PDF using the browser print API as fallback,
 * or renders a downloadable HTML invoice if DOMPDF is not available.
 *
 * For full PDF: Install DOMPDF via:
 *   composer require dompdf/dompdf
 * and place vendor/ inside the plugin folder.
 * This file auto-detects DOMPDF and falls back to HTML if not available.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_PDF_Invoice {

    /**
     * Check if DOMPDF is available.
     */
    public static function is_pdf_available() {
        return file_exists( TA_PLUGIN_DIR . 'vendor/autoload.php' );
    }

    /**
     * Generate invoice.
     * Returns binary PDF string if DOMPDF available, else HTML string.
     *
     * @param  object $order   DB row from ta_orders
     * @param  object $buyer   WP_User object
     * @param  object $ticket  DB row from ta_tickets
     * @return string          PDF binary or HTML
     */
    public static function generate( $order, $buyer, $ticket, $seller = null ) {
        $html = self::build_html( $order, $buyer, $ticket, $seller );

        // Try DOMPDF if installed
        if ( self::is_pdf_available() ) {
            require_once TA_PLUGIN_DIR . 'vendor/autoload.php';
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( 'A4', 'portrait' );
            $dompdf->render();
            return $dompdf->output();
        }

        // Fallback: return HTML (client can print to PDF)
        return $html;
    }

    private static function build_html( $order, $buyer, $ticket, $seller = null ) {
        $order_id_raw = is_object($order) ? $order->id : $order['id'];
        $order_id     = '#' . str_pad( $order_id_raw, 6, '0', STR_PAD_LEFT );
        
        $created_at   = is_object($order) ? $order->created_at : $order['createdAt'];
        $date         = date( 'd M Y', strtotime( $created_at ) );
        
        $event_date_raw = is_object($ticket) ? $ticket->event_date : $ticket['event_date'];
        $event_date     = date( 'd M Y', strtotime( $event_date_raw ) );
        
        $buyer_name   = esc_html( $buyer->display_name );
        $buyer_email  = esc_html( $buyer->user_email );
        
        $event_name   = is_object($ticket) ? esc_html($ticket->event_name) : esc_html($ticket['event_name']);
        $event_time   = is_object($ticket) ? esc_html($ticket->event_time) : esc_html($ticket['event_time']);
        
        $qty          = intval( is_object($order) ? $order->quantity : $order['quantity'] );
        $subtotal_val = is_object($order) ? $order->subtotal : $order['subtotal'];
        $fee_val      = is_object($order) ? $order->platform_fee : $order['platformFee'];
        $total_val    = is_object($order) ? $order->total_amount : $order['totalAmount'];
        
        $subtotal     = '₹' . number_format( $subtotal_val, 2 );
        $fee          = '₹' . number_format( $fee_val, 2 );
        $total        = '₹' . number_format( $total_val, 2 );
        
        $pay_id       = is_object($order) ? ($order->razorpay_payment_id ?: 'N/A') : ($order['razorpayPayId'] ?: 'N/A');
        
        $seller_name  = $seller ? esc_html($seller->display_name) : 'Verified Seller';
        $seller_phone = $seller ? get_user_meta($seller->ID, 'ta_phone', true) : 'N/A';

        $logo_url     = get_template_directory_uri() . '/public/images/logo.png';
        if (strpos($logo_url, 'http') !== 0) {
            $logo_url = home_url($logo_url);
        }
        $site_name    = get_bloginfo( 'name' );

        return "<!DOCTYPE html>
<html><head>
<meta charset='UTF-8'>
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;color:#1e293b;background:#f8fafc;padding:40px;}
  .invoice-container{max-width:800px;margin:0 auto;background:#fff;padding:50px;border-radius:8px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);}
  .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:50px;}
  .logo-img{height:50px;}
  .invoice-info{text-align:right;}
  .invoice-info h2{font-size:32px;color:#3b82f6;margin:0;font-weight:800;}
  .invoice-info p{color:#64748b;font-size:14px;margin-top:4px;}
  .details-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:30px;margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid #e2e8f0;}
  .detail-box h4{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:8px;}
  .detail-box p{font-size:14px;color:#1e293b;line-height:1.5;}
  table{width:100%;border-collapse:collapse;margin:30px 0;}
  th{background:#f1f5f9;color:#475569;padding:12px 15px;text-align:left;font-size:12px;text-transform:uppercase;font-weight:600;}
  td{padding:15px;font-size:14px;border-bottom:1px solid #f1f5f9;color:#1e293b;}
  .summary-table{width:300px;margin-left:auto;}
  .summary-table td{border-bottom:none;padding:8px 15px;}
  .summary-table td:first-child{color:#64748b;}
  .summary-table tr.total-row td{font-weight:bold;color:#3b82f6;font-size:18px;border-top:2px solid #3b82f6;padding-top:15px;margin-top:10px;}
  .badge{display:inline-block;background:#dcfce7;color:#166534;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:bold;margin-top:8px;}
  .footer{text-align:center;font-size:12px;color:#94a3b8;margin-top:50px;padding-top:20px;border-top:1px solid #e2e8f0;}
</style>
</head>
<body>
<div class='invoice-container'>
  <div class='header'>
    <img src='{$logo_url}' alt='TickerAdda' class='logo-img'>
    <div class='invoice-info'>
      <h2>INVOICE</h2>
      <p>Order {$order_id}</p>
      <p>PayID: {$pay_id}</p>
      <span class='badge'>PAID via Razorpay</span>
    </div>
  </div>

  <div class='details-grid'>
    <div class='detail-box'>
      <h4>Billed To</h4>
      <p><strong>{$buyer_name}</strong></p>
      <p>{$buyer_email}</p>
    </div>
    <div class='detail-box'>
      <h4>Seller Details</h4>
      <p><strong>{$seller_name}</strong></p>
      <p>Phone: {$seller_phone}</p>
    </div>
    <div class='detail-box'>
      <h4>Invoice Date</h4>
      <p>{$date}</p>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style='width:50%'>Description</th>
        <th>Event Date</th>
        <th>Qty</th>
        <th style='text-align:right'>Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <div style='font-weight:600;margin-bottom:4px;'>{$event_name}</div>
          <div style='font-size:12px;color:#64748b;'>Ticket Marketplace Purchase</div>
        </td>
        <td>{$event_date}<br>{$event_time}</td>
        <td>{$qty}</td>
        <td style='text-align:right'>{$subtotal}</td>
      </tr>
    </tbody>
  </table>

  <table class='summary-table'>
    <tr>
      <td>Subtotal</td>
      <td style='text-align:right'>{$subtotal}</td>
    </tr>
    <tr>
      <td>Platform Fee</td>
      <td style='text-align:right'>{$fee}</td>
    </tr>
    <tr class='total-row'>
      <td>Total Paid</td>
      <td style='text-align:right'>{$total}</td>
    </tr>
  </table>

  <div class='footer'>
    <p>Thank you for choosing {$site_name}!</p>
    <p>This is a computer generated invoice. For help, contact support@tickeradda.in</p>
    <p style='margin-top:10px;'>&copy; " . date('Y') . " {$site_name}. All rights reserved.</p>
  </div>
</div>
</body></html>";
    }
}
