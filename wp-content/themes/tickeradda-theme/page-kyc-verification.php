<?php
/**
 * Template Name: Kyc verification
 */
get_header();
?>

<style>
.kyc-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }
        .kyc-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .kyc-header h2 {
            font-size: 2rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #aaa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .upload-group {
            margin-bottom: 20px;
        }
        .upload-label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }
        .file-drop-area {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-drop-area:hover {
            border-color: var(--primary);
            background: rgba(59, 130, 246, 0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .kyc-info {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--primary);
            padding: 15px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            color: #ddd;
        }
</style>

<main id="main">
<div class="kyc-container">
        <div class="kyc-header">
            <h2>Verify Your Identity</h2>
            <p style="color: #888;">Complete KYC to start selling tickets on TickerAdda</p>
        </div>
        <div id="statusMessage" style="text-align: center; display: none;">
        </div>
        <div id="kycFormContainer">
            <div class="kyc-info">
                <i class="fas fa-shield-alt"></i> Your data is encrypted and stored securely. We only use this for
                verification purposes.
            </div>
            <form id="kycForm">
                <div class="form-group">
                    <label>Document Type</label>
                    <select name="documentType" id="documentType" class="form-input"
                        style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.3); color: white; border: 1px solid rgba(255,255,255,0.1);">
                        <option value="aadhaar">Aadhaar Card</option>
                        <option value="pan">PAN Card</option>
                        <option value="dl">Driving License</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Document Number</label>
                    <input type="text" id="documentNumber" class="form-input" placeholder="Enter ID Number" required
                        style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.3); color: white; border: 1px solid rgba(255,255,255,0.1);">
                </div>
                <div class="upload-group">
                    <label class="upload-label">Front Image</label>
                    <input type="file" id="frontImage" accept="image/*" hidden>
                    <div class="file-drop-area" id="dropFront">
                        <i class="fas fa-id-card"
                            style="font-size: 24px; margin-bottom: 10px; color: var(--primary);"></i>
                        <p id="frontFileName">Click to upload Front Side</p>
                    </div>
                </div>
                <div class="upload-group">
                    <label class="upload-label">Back Image (Optional for PAN)</label>
                    <input type="file" id="backImage" accept="image/*" hidden>
                    <div class="file-drop-area" id="dropBack">
                        <i class="fas fa-id-card"
                            style="font-size: 24px; margin-bottom: 10px; color: var(--primary);"></i>
                        <p id="backFileName">Click to upload Back Side</p>
                    </div>
                </div>
                <div class="upload-group">
                    <label class="upload-label">Selfie with ID</label>
                    <input type="file" id="selfie" accept="image/*" hidden>
                    <div class="file-drop-area" id="dropSelfie">
                        <i class="fas fa-user-check"
                            style="font-size: 24px; margin-bottom: 10px; color: var(--primary);"></i>
                        <p id="selfieFileName">Click to upload Selfie</p>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Submit for
                    Verification</button>
            </form>
        </div>
    </div>
</main>
<?php get_footer(); ?>
