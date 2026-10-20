/**
 * RYDO — Main Frontend Logic
 * Supports: Bike Taxi, Auto, Cab & RYDO Parcel (Logistics Delivery)
 * Live fare estimation, service tab toggles, form validation & dynamic calculations
 */

(function () {
    "use strict";

    /* -------------------------------------------------------
       Predefined Route Distances (km) — Mirrors config/routes.php
    ------------------------------------------------------- */
    const ROUTES = {
        "KIT College of Engg|Central Bus Stand":  8.5,
        "KIT College of Engg|Railway Station":    9.2,
        "KIT College of Engg|Mahalaxmi Temple":   11.0,
        "KIT College of Engg|Rankala Lake":       12.5,
        "KIT College of Engg|DYP City Mall":      7.8,
        "KIT College of Engg|Shivaji University": 4.2,
        "KIT College of Engg|CPR Hospital":       10.2,
        "KIT College of Engg|Shiroli MIDC":       15.0,
        "Central Bus Stand|Railway Station":      1.5,
        "Central Bus Stand|Mahalaxmi Temple":     4.0,
        "Central Bus Stand|Rankala Lake":         5.5,
        "Central Bus Stand|DYP City Mall":        2.8,
        "Central Bus Stand|Shivaji University":   6.2,
        "Central Bus Stand|CPR Hospital":         2.2,
        "Central Bus Stand|Shiroli MIDC":         7.5,
        "Railway Station|Mahalaxmi Temple":       3.5,
        "Railway Station|Rankala Lake":           5.2,
        "Railway Station|DYP City Mall":          3.2,
        "Railway Station|Shivaji University":     6.5,
        "Railway Station|CPR Hospital":           1.8,
        "Railway Station|Shiroli MIDC":           8.0,
        "Mahalaxmi Temple|Rankala Lake":          2.8,
        "Mahalaxmi Temple|DYP City Mall":         5.2,
        "Mahalaxmi Temple|Shivaji University":    7.5,
        "Mahalaxmi Temple|CPR Hospital":          3.8,
        "Rankala Lake|DYP City Mall":             6.5,
        "Rankala Lake|Shivaji University":        8.2,
        "Rankala Lake|CPR Hospital":              4.5,
        "DYP City Mall|Shivaji University":       4.8,
        "DYP City Mall|CPR Hospital":             3.0,
        "DYP City Mall|Shiroli MIDC":             6.2,
        "Shivaji University|CPR Hospital":        7.0,
        "CPR Hospital|Shiroli MIDC":              8.5,
        "Central Bus Stand|Airport":              9.5,
        "Railway Station|Airport":               10.2,
        "KIT College of Engg|Airport":            6.5,
        "DYP City Mall|Airport":                  8.8,
    };

    /* -------------------------------------------------------
       Fare Structure — Mirrors config/fares.php
    ------------------------------------------------------- */
    const FARES = {
        Bike:   { base_fare: 20, per_km: 8,  base_km: 2 },
        Auto:   { base_fare: 30, per_km: 12, base_km: 2 },
        Cab:    { base_fare: 50, per_km: 16, base_km: 2 },
        Parcel: { base_fare: 25, per_km: 10, base_km: 2 },
    };

    function getDistance(pickup, destination) {
        if (!pickup || !destination) return null;
        if (pickup === destination) return 0;
        const key1 = pickup + "|" + destination;
        const key2 = destination + "|" + pickup;
        return ROUTES[key1] ?? ROUTES[key2] ?? 6.0; // Realistic default if custom selected
    }

    function calcFare(distanceKm, vehicleType) {
        const f = FARES[vehicleType] || FARES.Bike;
        if (distanceKm <= f.base_km) return f.base_fare;
        return f.base_fare + ((distanceKm - f.base_km) * f.per_km);
    }

    /* -------------------------------------------------------
       Elements
    ------------------------------------------------------- */
    const pickupSel      = document.getElementById("pickup");
    const destinationSel = document.getElementById("destination");
    const farePreview    = document.getElementById("fare-preview");
    const hiddenDist     = document.getElementById("distance_km");
    const hiddenFare     = document.getElementById("fare_estimate");
    const serviceTypeInput = document.getElementById("service_type");
    const parcelFields   = document.getElementById("parcel-details-group");
    const rideOptions    = document.getElementById("ride-vehicle-options");
    const bookingForm    = document.getElementById("booking-form");

    function getSelectedVehicle() {
        const activeService = serviceTypeInput ? serviceTypeInput.value : 'ride';
        if (activeService === 'parcel') return 'Parcel';

        const checkedRadio = document.querySelector('input[name="vehicle_type"]:checked');
        return checkedRadio ? checkedRadio.value : 'Bike';
    }

    function updateFarePreview() {
        if (!pickupSel || !destinationSel || !farePreview) return;

        const pickup      = pickupSel.value;
        const destination = destinationSel.value;
        const vehicle     = getSelectedVehicle();

        // Prevent identical pickup and drop
        Array.from(destinationSel.options).forEach(opt => {
            opt.disabled = (opt.value && opt.value === pickup);
        });
        Array.from(pickupSel.options).forEach(opt => {
            opt.disabled = (opt.value && opt.value === destination);
        });

        if (!pickup || !destination) {
            farePreview.innerHTML = '<p class="text-muted">Select pickup and drop location to view estimated fare.</p>';
            if (hiddenDist) hiddenDist.value = "";
            if (hiddenFare) hiddenFare.value = "";
            return;
        }

        const distance = getDistance(pickup, destination);
        const fare = calcFare(distance, vehicle);

        if (hiddenDist) hiddenDist.value = distance.toFixed(2);
        if (hiddenFare) hiddenFare.value = fare.toFixed(2);

        const vehicleLabels = {
            Bike: 'Bike taxi',
            Auto: 'Auto',
            Cab:  'Cab',
            Parcel: 'Parcel delivery'
        };

        farePreview.innerHTML = `
            <div class="fare-header-preview">
                <span class="fare-amount">₹${fare.toFixed(2)}</span>
                <span class="badge badge-confirmed">${vehicleLabels[vehicle]}</span>
            </div>
            <div class="fare-meta">
                Est. Distance: <strong>${distance} km</strong> &nbsp;|&nbsp;
                Duration: <strong>~${Math.round(distance * 3.2)} mins</strong>
            </div>
            <div class="fare-note">
                Fares are estimates. A 4-digit verification PIN is issued on confirmation.
            </div>
        `;
    }

    // Service Switcher (Ride vs Parcel)
    window.switchService = function (type) {
        if (serviceTypeInput) serviceTypeInput.value = type;

        const tabRide = document.getElementById("tab-ride");
        const tabParcel = document.getElementById("tab-parcel");

        if (type === 'parcel') {
            if (tabRide) tabRide.classList.remove("active");
            if (tabParcel) tabParcel.classList.add("active");
            if (parcelFields) parcelFields.style.display = "block";
            if (rideOptions) rideOptions.style.display = "none";
        } else {
            if (tabParcel) tabParcel.classList.remove("active");
            if (tabRide) tabRide.classList.add("active");
            if (parcelFields) parcelFields.style.display = "none";
            if (rideOptions) rideOptions.style.display = "grid";
        }
        updateFarePreview();
    };

    if (pickupSel) pickupSel.addEventListener("change", updateFarePreview);
    if (destinationSel) destinationSel.addEventListener("change", updateFarePreview);

    document.querySelectorAll('input[name="vehicle_type"]').forEach(r => {
        r.addEventListener("change", updateFarePreview);
    });

    // Clean Phone Inputs
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener("input", () => {
            input.value = input.value.replace(/[^0-9]/g, "").slice(0, 10);
        });
    });

    // Form submission validation
    if (bookingForm) {
        bookingForm.addEventListener("submit", function (e) {
            const pickup = pickupSel.value;
            const dest = destinationSel.value;

            if (!pickup || !dest) {
                e.preventDefault();
                alert("Please select both Pickup and Destination locations.");
                return;
            }

            if (pickup === dest) {
                e.preventDefault();
                alert("Pickup and Destination cannot be the same.");
                return;
            }

            const activeService = serviceTypeInput ? serviceTypeInput.value : 'ride';
            if (activeService === 'parcel') {
                const rName = document.getElementById("receiver_name");
                const rPhone = document.getElementById("receiver_phone");
                if (!rName || !rName.value.trim() || !rPhone || !rPhone.value.trim()) {
                    e.preventDefault();
                    alert("Please provide Receiver Name and Contact Number for RYDO Parcel delivery.");
                    return;
                }
            }

            const submitBtn = bookingForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Processing booking…</span>';
            }
        });
    }

    // Auto-calculate on initial load if pre-selected
    updateFarePreview();

    // Auto-dismiss alerts
    document.querySelectorAll(".alert").forEach(alert => {
        setTimeout(() => {
            alert.style.transition = "opacity .5s";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

})();
