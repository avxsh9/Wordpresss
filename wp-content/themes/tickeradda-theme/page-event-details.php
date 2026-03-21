<?php
/**
 * Template Name: Event details
 */
get_header();
?>

<main id="main">
<section class="section" style="padding-top: 100px;">
        <div class="container">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
                <div>
                    <div class="event-hero-image"
                        style="width: 100%; height: 400px; border-radius: 20px; overflow: hidden; margin-bottom: 30px;">
                        <img id="eventImage"
                            src="https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=600&h=400&fit=crop"
                            style="width: 100%; height: 100%; object-fit: cover;" alt="Event">
                    </div>
                    <h1 id="eventTitle" style="font-size: 42px; margin-bottom: 10px;">Arijit Singh Live</h1>
                    <div style="display: flex; gap: 20px; color: var(--text-gray); margin-bottom: 30px;">
                        <span><i class="far fa-calendar"></i> <span id="eventDate">Sat, 15 Nov</span></span>
                        <span><i class="far fa-clock"></i> <span id="eventTime">7:00 PM</span></span>
                        <span><i class="fas fa-map-marker-alt"></i> <span id="eventVenue">NSCI Dome,
                                Mumbai</span></span>
                    </div>
                    <div
                        style="background: var(--card-bg); padding: 30px; border-radius: 12px; border: 1px solid var(--glass-border); margin-bottom: 30px;">
                        <h3>About the Event</h3>
                        <p style="color: var(--text-gray); line-height: 1.6; margin-top: 10px;">
                            Experience the magic of Arijit Singh live in concert! This is going to be an unforgettable
                            night filled with his biggest hits, soulful melodies, and an electric atmosphere. Don't miss
                            out on the musical event of the year.
                        </p>
                    </div>
                </div>
                <div>
                    <div
                        style="background: var(--card-bg); padding: 30px; border-radius: 20px; border: 1px solid var(--glass-border); position: sticky; top: 100px;">
                        <h3 style="margin-bottom: 20px;">Buy Tickets</h3>
                        <div
                            style="margin-bottom: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--primary);">
                            <div>
                                <div style="font-weight: 600;">Diamond Stand</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Category A</div>
                            </div>
                            <div style="font-weight: 700; color: var(--primary);">₹4,500</div>
                        </div>
                        <div
                            style="margin-bottom: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600;">Gold Stand</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Category B</div>
                            </div>
                            <div style="font-weight: 700; color: white;">₹2,500</div>
                        </div>
                        <button class="btn btn-primary" onclick="alert('Redirecting to checkout...')"
                            style="width: 100%; justify-content: center;">
                            Proceed to Book
                        </button>
                        <p style="text-align: center; font-size: 12px; color: var(--text-gray); margin-top: 15px;">
                            <i class="fas fa-shield-alt"></i> 100% Buyer Guarantee
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    
    <script>
        // Simple mock data logic to update page based on 'id' param
        const urlParams = new URLSearchParams(window.location.search);
        const eventId = urlParams.get('id');
        // Mock Data
        const events = {
            '1': { title: 'Arijit Singh Live', img: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=600&h=400&fit=crop', date: 'Sat, 15 Nov', venue: 'NSCI Dome, Mumbai' },
            '2': { title: 'MI vs CSK - IPL 2026', img: 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?w=600&h=400&fit=crop', date: 'Sun, 20 Apr', venue: 'Wankhede Stadium, Mumbai' },
            '3': { title: 'Bollywood Night Live', img: 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&h=400&fit=crop', date: 'Fri, 08 Dec', venue: 'Gachibowli Stadium, Hyderabad' },
            '6': { title: 'Coldplay World Tour', img: 'https://images.unsplash.com/photo-1459749411177-8c29142af60e?w=600&h=400&fit=crop', date: 'Sat, 24 Feb', venue: 'DY Patil Stadium, Mumbai' }
        };
        if (events[eventId]) {
            document.getElementById('eventTitle').innerText = events[eventId].title;
            document.getElementById('eventImage').src = events[eventId].img;
            document.getElementById('eventDate').innerText = events[eventId].date;
            document.getElementById('eventVenue').innerText = events[eventId].venue;
        }
    </script>
</main>
<?php get_footer(); ?>
