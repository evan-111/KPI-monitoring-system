<div class="content active fade-in">
  <div class="dash-grid">

    <!-- ══════════════════════════════════════
         PERFORMANCE THRESHOLD RULES
    ══════════════════════════════════════ -->
    <div class="card col-full">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><path d="M8 12h8M12 8v8"/>
        </svg>
        Performance Threshold Rules
      </div>
      
      <div style="margin-top: 12px;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
          
          <!-- Top Performer Threshold -->
          <div style="background: var(--card-hover); padding: 16px; border-radius: 8px; border-left: 3px solid #22c55e;">
            <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">Top Performer Threshold</div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <input type="number" value="4.5" min="1" max="5" step="0.1" style="width: 80px; padding: 8px; background: #141c2b; border: 1px solid var(--border); border-radius: 6px; color: var(--text-primary); font-weight: 600;">
              <span style="color: var(--text-secondary); font-size: 12px;">KPI Score or above</span>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">Employees scoring at or above this threshold are classified as top performers.</p>
          </div>

          <!-- At-Risk Threshold -->
          <div style="background: var(--card-hover); padding: 16px; border-radius: 8px; border-left: 3px solid #ef4444;">
            <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">At-Risk Threshold</div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <input type="number" value="3.0" min="1" max="5" step="0.1" style="width: 80px; padding: 8px; background: #141c2b; border: 1px solid var(--border); border-radius: 6px; color: var(--text-primary); font-weight: 600;">
              <span style="color: var(--text-secondary); font-size: 12px;">KPI Score or below</span>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">Employees scoring below this threshold require improvement plans.</p>
          </div>

          <!-- Performance Warning Trend -->
          <div style="background: var(--card-hover); padding: 16px; border-radius: 8px; border-left: 3px solid #f59e0b;">
            <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">Decline Alert Threshold</div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <input type="number" value="0.5" min="0" max="5" step="0.1" style="width: 80px; padding: 8px; background: #141c2b; border: 1px solid var(--border); border-radius: 6px; color: var(--text-primary); font-weight: 600;">
              <span style="color: var(--text-secondary); font-size: 12px;">Point decline per period</span>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">Alert if KPI declines by more than this amount.</p>
          </div>

        </div>
        
        <button onclick="alert('Threshold settings saved!')" style="margin-top: 16px; padding: 11px 20px; background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; border-radius: 8px; color: white; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
          Save Thresholds
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         NOTIFICATION SETTINGS
    ══════════════════════════════════════ -->
    <div class="card col-full">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        Notification Settings
      </div>
      
      <div style="margin-top: 12px;">
        <!-- Toggle Switches -->
        <div class="notif-row-setting" data-key="at_risk_alert" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border);">
          <div>
            <p style="font-size: 12px; font-weight: 600; color: var(--text-primary);">At-Risk Alert Notifications</p>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Notify when employees fall below threshold</p>
          </div>
          <label class="switch-toggle">
            <input type="checkbox" id="notif-at-risk" class="real-toggle">
            <span class="custom-toggle">
              <span class="custom-toggle-thumb"></span>
            </span>
          </label>
        </div>

        <div class="notif-row-setting" data-key="decline_alert" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border);">
          <div>
            <p style="font-size: 12px; font-weight: 600; color: var(--text-primary);">Performance Decline Alerts</p>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Alert on significant score drops</p>
          </div>
          <label class="switch-toggle">
            <input type="checkbox" id="notif-decline" class="real-toggle">
            <span class="custom-toggle">
              <span class="custom-toggle-thumb"></span>
            </span>
          </label>
        </div>

        <div class="notif-row-setting" data-key="monthly_report" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border);">
          <div>
            <p style="font-size: 12px; font-weight: 600; color: var(--text-primary);">Monthly Report Summary</p>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Receive monthly KPI analysis reports</p>
          </div>
          <label class="switch-toggle">
            <input type="checkbox" id="notif-monthly" class="real-toggle">
            <span class="custom-toggle">
              <span class="custom-toggle-thumb"></span>
            </span>
          </label>
        </div>

        <div class="notif-row-setting" data-key="email" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0;">
          <div>
            <p style="font-size: 12px; font-weight: 600; color: var(--text-primary);">Email Notifications</p>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Send all notifications via email</p>
          </div>
          <label class="switch-toggle">
            <input type="checkbox" id="notif-email" class="real-toggle">
            <span class="custom-toggle">
              <span class="custom-toggle-thumb"></span>
            </span>
          </label>
        <style>
        .switch-toggle {
          display: inline-block;
          width: 44px;
          height: 24px;
          background: #fff;
          border-radius: 12px;
          position: relative;
          transition: background 0.3s, border 0.3s;
          border: 2px solid #e5e7eb;
          box-sizing: border-box;
        }
        .real-toggle {
          position: absolute;
          opacity: 0;
          width: 44px;
          height: 24px;
          margin: 0;
          cursor: pointer;
          z-index: 2;
        }
        .custom-toggle {
          display: block;
          width: 44px;
          height: 24px;
          border-radius: 12px;
          position: relative;
          transition: background 0.3s, border 0.3s;
          background: #fff;
          border: none;
        }
        .custom-toggle-thumb {
          position: absolute;
          top: 2px;
          left: 2px;
          width: 20px;
          height: 20px;
          background: #fff;
          border-radius: 50%;
          transition: all 0.3s;
          box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .real-toggle:checked + .custom-toggle {
          background: #22c55e;
          border: 2px solid #22c55e;
        }
        .real-toggle:checked + .custom-toggle .custom-toggle-thumb {
          left: 22px;
        }
        </style>
        </div>
      </div>
      <script>
      // Notification Settings Toggle Logic (inline for settings.php only)
      document.addEventListener('DOMContentLoaded', function() {
        // Set default states (all ON except email)
        document.getElementById('notif-at-risk').checked = true;
        document.getElementById('notif-decline').checked = true;
        document.getElementById('notif-monthly').checked = true;
        document.getElementById('notif-email').checked = false;

        // Save on toggle 
        document.querySelectorAll('.notif-row-setting .real-toggle').forEach(cb => {
          cb.addEventListener('change', function() {

          });
        });
      });
      </script>
      </div>
    </div>

  

      </div>
    </div>

  </div>
</div>
