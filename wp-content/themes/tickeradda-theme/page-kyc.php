<?php
/**
 * Template Name: KYC Verification
 * Description: Seller KYC document submission form.
 */
get_header();
?>
<style>
.kyc-container {
    max-width: 600px;
    margin: 40px auto;
    padding: var(--section-pad-x);
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    backdrop-filter: blur(10px);
}
@media (max-width: 640px) {
    .kyc-container { padding: 20px; margin: 20px auto; }
}
.upload-group { margin-bottom: 20px; }
.upload-label { display: block; margin-bottom: 8px; color: #ccc; }
.file-drop-area {
    border: 2px dashed rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}
.file-drop-area:hover { border-color: var(--color-primary); background: rgba(59, 130, 246, 0.1); }
.kyc-info {
    background: rgba(59, 130, 246, 0.1);
    border-left: 4px solid var(--color-primary);
    padding: 15px;
    margin-bottom: 25px;
    font-size: 0.9rem;
    color: #ddd;
}
</style>
<main id="main">
<section class="section" style="padding-top:40px;">
    <div class="container">
        <div class="kyc-container">
            <div style="margin-bottom: 20px;">
                <a href="<?php echo home_url('/seller-dashboard/'); ?>" style="color: var(--color-text-muted); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div style="margin-bottom:28px; text-align: center;">
                <h1 style="font-size:2rem;">Verify Your Identity</h1>
                <p style="color:var(--color-text-muted);margin-top:8px;">
                    Complete KYC to start selling tickets on TickerAdda.
                </p>
            </div>
            <div id="ta-kyc-form-container"></div>
        </div>
    </div>
</section>
</main>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const c = document.getElementById('ta-kyc-form-container');
    if (!c) return;

    let status = { status: 'none' };
    try {
        const res = await fetch(TA.restUrl + '/kyc/status', {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        if (res.ok) status = await res.json();
    } catch (e) { console.error('KYC Status check failed', e); }

    if (status.status === 'approved') {
        c.innerHTML = `<div class="alert alert-success" style="text-align:center; padding: 40px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px;">
            <i class="fas fa-check-circle" style="font-size: 3rem; display:block; margin-bottom:15px; color: #10B981;"></i>
            <h3>KYC Approved!</h3>
            <p>You can now list your tickets for sale.</p>
            <a href="<?php echo esc_url(home_url('/sell-ticket/')); ?>" class="btn btn-primary" style="margin-top:20px; display: inline-block;">Sell Tickets</a>
        </div>`;
        return;
    }
    if (status.status === 'pending') {
        c.innerHTML = `<div class="alert alert-info" style="text-align:center; padding: 40px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px;">
            <i class="fas fa-clock" style="font-size: 3rem; display:block; margin-bottom:15px; color: #3b82f6;"></i>
            <h3>Under Review</h3>
            <p>Your documents are being reviewed. This usually takes less than 24 hours.</p>
        </div>`;
        return;
    }

    c.innerHTML = `
    ${status.status === 'rejected' ? `<div class="alert alert-danger" style="margin-bottom: 20px;">❌ KYC Rejected: ${status.rejectionReason || 'Please resubmit with correct documents.'}</div>` : ''}
    <div class="kyc-info">
        <i class="fas fa-shield-alt"></i> Your data is encrypted and stored securely according to RBI guidelines.
    </div>
    <div class="form-group">
        <label class="form-label">Document Type *</label>
        <select id="kyc-doc-type" class="form-input">
            <option value="aadhaar">Aadhaar Card</option>
            <option value="pan">PAN Card</option>
            <option value="voter_id">Voter ID</option>
            <option value="passport">Passport</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Document Number *</label>
        <input type="text" id="kyc-doc-number" class="form-input" placeholder="e.g. 1234 5678 9012">
    </div>
    
    <div class="upload-group">
        <label class="upload-label">Front Image *</label>
        <input type="file" id="frontImage" accept="image/*" hidden>
        <div class="file-drop-area" id="dropFront">
            <i class="fas fa-id-card" style="font-size: 24px; margin-bottom: 10px; color: #3b82f6;"></i>
            <p id="frontFileName">Click to upload Front Side</p>
        </div>
    </div>
    
    <div class="upload-group">
        <label class="upload-label">Back Image (Optional for PAN)</label>
        <input type="file" id="backImage" accept="image/*" hidden>
        <div class="file-drop-area" id="dropBack">
            <i class="fas fa-id-card" style="font-size: 24px; margin-bottom: 10px; color: #3b82f6;"></i>
            <p id="backFileName">Click to upload Back Side</p>
        </div>
    </div>
    
    <div class="upload-group">
        <label class="upload-label">Selfie with ID *</label>
        <input type="file" id="selfie" accept="image/*" hidden>
        <div class="file-drop-area" id="dropSelfie">
            <i class="fas fa-user-check" style="font-size: 24px; margin-bottom: 10px; color: #3b82f6;"></i>
            <p id="selfieFileName">Click to upload Selfie</p>
        </div>
    </div>

    <div id="kyc-error"   class="alert alert-danger"  style="display:none; margin-top:15px;"></div>
    <div id="kyc-success" class="alert alert-success" style="display:none; margin-top:15px;"></div>
    <button id="kyc-submit" class="btn btn-primary" style="width:100%; margin-top:20px;">Submit for Verification</button>`;

    // Helper for file uploads
    const setupFile = (inputId, dropId, nameId) => {
        const input = document.getElementById(inputId);
        const drop = document.getElementById(dropId);
        const name = document.getElementById(nameId);
        if (!input || !drop || !name) return;
        drop.onclick = () => input.click();
        input.onchange = () => {
            if(input.files[0]) {
                name.textContent = input.files[0].name;
                drop.style.borderColor = '#3b82f6';
                drop.style.background = 'rgba(59, 130, 246, 0.05)';
            }
        };
    };
    setupFile('frontImage', 'dropFront', 'frontFileName');
    setupFile('backImage', 'dropBack', 'backFileName');
    setupFile('selfie', 'dropSelfie', 'selfieFileName');

    document.getElementById('kyc-submit').onclick = async () => {
        const btn = document.getElementById('kyc-submit');
        const err = document.getElementById('kyc-error');
        const suc = document.getElementById('kyc-success');
        err.style.display = 'none'; suc.style.display = 'none';

        const docType = document.getElementById('kyc-doc-type').value;
        const docNumber = document.getElementById('kyc-doc-number').value.trim();
        
        const f = document.getElementById('frontImage').files[0];
        const b = document.getElementById('backImage').files[0];
        const s = document.getElementById('selfie').files[0];

        if (!docNumber) { showAlert('Error', 'Please enter document number', 'error'); return; }
        if (!f || !s) { showAlert('Error', 'Front image and Selfie are required.', 'error'); return; }
        
        const fd = new FormData();
        fd.append('documentType', docType);
        fd.append('documentNumber', docNumber);
        fd.append('frontImage', f);
        if(b) fd.append('backImage', b);
        fd.append('selfie', s);

        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
        try {
            const res = await fetch(TA.restUrl + '/kyc/submit', {
                method: 'POST',
                headers: { 'X-WP-Nonce': TA.nonce },
                body: fd
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || data.msg || 'Submission failed');

            suc.textContent = '✅ KYC submitted! Admin will review within 24 hours.';
            suc.style.display = 'block';
            setTimeout(() => location.reload(), 2000);
        } catch (e) {
            err.textContent = e.message;
            err.style.display = 'block';
            btn.disabled = false; btn.textContent = 'Submit for Verification';
        }
    };
});
</script>
<?php get_footer(); ?>
