<?php
/**
 * Template Name: Sell ticket
 */
get_header();
?>

<main id="main">
<section class="section">
        <div class="container" style="max-width: 800px; margin-top: 100px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <a href="<?php echo esc_url(home_url('/seller-dashboard/')); ?>" style="color: var(--color-text-muted); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <h1 style="margin-bottom: 30px; text-align: center;">List Your Ticket</h1>
            <div
                style="background: var(--card-bg); padding: 40px; border-radius: 20px; border: 1px solid var(--glass-border);">
                <form id="sellTicketForm">
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Select or Type
                            Event Name</label>
                        <input type="hidden" id="eventId" name="event_id" value="<?php echo isset($_GET['event_id']) ? esc_attr($_GET['event_id']) : ''; ?>">
                        <input list="events" id="ticketEvent" name="event" placeholder="Choose from list or type your own..."
                            style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                        <datalist id="events">
                            <?php
                            $all_post_types = array('events', 'sports', 'ta_sports', 'movies', 'sports_events');
                            $events_query = get_posts(array(
                                'post_type' => $all_post_types,
                                'posts_per_page' => -1,
                                'post_status' => 'publish'
                            ));
                            $event_details = array();
                            foreach ($events_query as $ev) :
                                $cat_terms = wp_get_post_terms($ev->ID, 'event_cat', array('fields' => 'slugs'));
                                $cat_type  = !empty($cat_terms) ? $cat_terms[0] : strtolower($ev->post_type);
                                $event_details[] = array(
                                    'id'       => $ev->ID,
                                    'name'     => $ev->post_title,
                                    'date'     => get_post_meta($ev->ID, 'event_date', true),
                                    'time'     => get_post_meta($ev->ID, 'event_time', true),
                                    'location' => get_post_meta($ev->ID, 'event_location', true) ?: get_post_meta($ev->ID, 'venue', true),
                                    'category' => $cat_type
                                );
                            ?>
                                <option value="<?php echo esc_attr($ev->post_title); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <script>window.taEvents = <?php echo json_encode($event_details); ?>;</script>
                        <div id="manualEventToggle" style="margin-top: 10px; text-align: right;">
                            <button type="button" id="btnListManually" style="background: none; border: none; color: var(--primary); cursor: pointer; font-size: 0.85rem; text-decoration: underline;">
                                <i class="fas fa-plus-circle"></i> Don't see your event? List it manually
                            </button>
                        </div>
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Event
                            Category</label>
                        <select id="ticketCategory" name="category" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; appearance: none;">
                            <option value="movies">Movies</option>
                            <option value="music">Music / Concert</option>
                            <option value="sports">Sports / Matches</option>
                            <option value="comedy">Standup Comedy</option>
                            <option value="theatre">Theatre / Plays</option>
                            <option value="other">Other Events</option>
                        </select>
                    </div>

                    <div id="movieLanguageContainer" style="margin-bottom: 25px; display: none;">
                        <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Language (For Movies Only)</label>
                        <select id="ticketLanguage" name="movieLanguage" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; appearance: none;">
                            <option value="">-- Select Language --</option>
                            <option value="Hindi">Hindi</option>
                            <option value="English">English</option>
                            <option value="Tamil">Tamil</option>
                            <option value="Telugu">Telugu</option>
                            <option value="Kannada">Kannada</option>
                            <option value="Malayalam">Malayalam</option>
                            <option value="Marathi">Marathi</option>
                            <option value="Bengali">Bengali</option>
                            <option value="Punjabi">Punjabi</option>
                            <option value="Gujarati">Gujarati</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Venue</label>
                        <input type="text" id="ticketVenue" placeholder="e.g. Narendra Modi Stadium"
                            style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                    </div>
                    <div class="grid grid-3" style="margin-bottom: 25px;">
                        <div>
                            <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Ticket Section</label>
                            <input type="text" id="ticketSection" placeholder="e.g. Diamond"
                                style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                        </div>
                        <div>
                            <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Row</label>
                            <input type="text" id="ticketRow" placeholder="e.g. B"
                                style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                        </div>
                        <div>
                            <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Seat (Optional)</label>
                            <input type="text" id="ticketSeat" placeholder="e.g. 12"
                                style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div style="flex: 1;">
                            <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Event
                                Date</label>
                            <input type="date" id="ticketEventDate" name="eventDate"
                                style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; color-scheme: dark;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Event
                                Time</label>
                            <input type="time" id="ticketEventTime" name="eventTime"
                                style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; color-scheme: dark;">
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                        <div style="flex: 1;">
                            <label
                                style="display: block; color: var(--text-gray); margin-bottom: 10px;">Quantity</label>
                            <input type="number" id="ticketQuantity" min="1" value="1"
                                style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; color: var(--text-gray); margin-bottom: 10px;">Price per
                                Ticket (₹)</label>
                            <input type="number" id="ticketPrice" placeholder="0.00"
                                style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                        </div>
                    </div>
                    <div id="proofDropZone"
                        style="margin-bottom: 25px; padding: 30px; border: 2px dashed var(--glass-border); border-radius: 10px; text-align: center; cursor: pointer; transition: 0.3s; background: rgba(16, 185, 129, 0.05);">
                        <input type="file" id="paymentProofFile" hidden accept=".pdf,.jpg,.jpeg,.png">
                        <i class="fas fa-file-invoice-dollar"
                            style="font-size: 30px; color: #10b981; margin-bottom: 10px;"></i>
                        <p style="color: var(--text-gray); margin-bottom: 5px;">Upload Payment Proof <span style="color:#ef4444; font-weight: bold;">*MANDATORY*</span></p>
                        <p style="font-size: 0.85rem; color: #888; margin-bottom: 5px;">(e.g., booking screenshot, payment receipt)</p>
                        <p id="proofFileName"
                            style="color: #10b981; font-weight: 600; font-size: 0.9rem; min-height: 20px;">
                        </p>
                    </div>

                    <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 10px; margin-bottom: 30px; border: 1px solid var(--glass-border);">
                        <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                            <input type="checkbox" id="legalAgreement" style="margin-top: 5px; width: 18px; height: 18px; accent-color: var(--primary);">
                            <span style="color: var(--text-gray); font-size: 0.9rem; line-height: 1.5;">
                                <strong style="color: white;">I confirm that:</strong><br>
                                • I have legally purchased this ticket.<br>
                                • I will NOT use this ticket after selling.<br>
                                • I understand that fraud may result in account suspension and legal action.
                            </span>
                        </label>
                    </div>

                    <button type="button" id="submitTicketBtn" class="btn btn-primary"
                        style="width: 100%; justify-content: center; font-size: 18px;">
                        List Ticket for Sale
                    </button>
                </form>
            </div>
        </div>
    </section>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</main>
<?php get_footer(); ?>
