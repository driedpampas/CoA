var map = L.map("map").setView([47.1622, 27.5889], 13);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
	attribution:
		'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
	maxZoom: 19,
}).addTo(map);

function getMarkerColor(eventType) {
	var colors = {
		earthquake: "#d32f2f",
		flood: "#1565c0",
		fire: "#e65100",
		storm: "#6a1b9a",
		other: "#546e7a",
	};
	return colors[eventType] || "#546e7a";
}

function getMarkerIcon(color) {
	return L.divIcon({
		className: "custom-marker",
		html: `<div style="background:${color};width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.4);"></div>`,
		iconSize: [14, 14],
		iconAnchor: [7, 7],
	});
}

var shelterIcon = L.divIcon({
	className: "custom-marker",
	html: '<div style="background:#4caf50;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.4);"></div>',
	iconSize: [14, 14],
	iconAnchor: [7, 7],
});

var eventMarkers = [];
var shelterMarkers = [];

eventsData.forEach((event) => {
	if (!event.latitude || !event.longitude) return;

	var color = getMarkerColor(event.event_type);
	var marker = L.marker([event.latitude, event.longitude], {
		icon: getMarkerIcon(color),
	}).addTo(map);

	marker.bindPopup(
		`<strong>${event.title}</strong><br><span class="badge badge-${event.event_type}" style="font-size:11px;">${event.event_type}</span><br>Severity: ${event.severity}<br><small>${event.started_at}</small><br><p style='margin-top:6px;font-size:12px;'>${(event.description || "").substring(0, 150)}</p>`,
	);

	eventMarkers.push(marker);
});

sheltersData.forEach((shelter) => {
	if (!shelter.latitude || !shelter.longitude) return;

	var marker = L.marker([shelter.latitude, shelter.longitude], {
		icon: shelterIcon,
	}).addTo(map);

	marker.bindPopup(
		`<strong>${shelter.name}</strong><br><span style="color:#4caf50;font-weight:600;">SHELTER</span> <span style="font-size:11px;color:#666;">${shelter.shelter_type}</span><br>${shelter.address}<br>Capacity: ${shelter.current_occupancy} / ${shelter.capacity}<br>Status: ${shelter.status}${shelter.contact_phone ? `<br>Phone: ${shelter.contact_phone}` : ""}`,
	);

	shelterMarkers.push(marker);
});

var userMarker = null;
var userAccuracyCircle = null;
var routeLayers = [];
var locationBannerEl = document.querySelector("#locationBanner");
var locationBannerTextEl = document.querySelector("#locationBannerText");

function showLocationBanner(text, isError) {
	if (!locationBannerEl) return;
	locationBannerTextEl.textContent = text;
	locationBannerEl.classList.remove(
		"location-banner--hidden",
		"location-banner--error",
		"location-banner--success",
	);
	locationBannerEl.classList.add(
		isError ? "location-banner--error" : "location-banner--success",
	);
	var spinner = document.querySelector("#locationSpinner");
	if (spinner) spinner.style.display = "none";
}

function hideLocationBanner() {
	if (!locationBannerEl) return;
	locationBannerEl.classList.add("location-banner--hidden");
}

function showLocationSpinner() {
	if (!locationBannerEl) return;
	locationBannerTextEl.textContent = "Obtaining location...";
	locationBannerEl.classList.remove(
		"location-banner--hidden",
		"location-banner--error",
		"location-banner--success",
	);
	var spinner = document.querySelector("#locationSpinner");
	if (spinner) spinner.style.display = "";
}

var userLocationIcon = L.divIcon({
	className: "custom-marker",
	html: '<div style="position:relative;width:18px;height:18px;"><div style="position:absolute;top:0;left:0;width:18px;height:18px;background:rgba(33,150,243,0.2);border-radius:50%;animation:pulse 2s infinite;"></div><div style="position:absolute;top:3px;left:3px;width:12px;height:12px;background:#2196f3;border-radius:50%;border:3px solid #fff;box-shadow:0 1px 6px rgba(0,0,0,0.5);"></div></div>',
	iconSize: [18, 18],
	iconAnchor: [9, 9],
});

function updateUserMarker(lat, lng, accuracy) {
	if (userMarker) {
		userMarker.setLatLng([lat, lng]);
	} else {
		userMarker = L.marker([lat, lng], { icon: userLocationIcon })
			.addTo(map)
			.bindPopup("Your location");
	}

	if (userAccuracyCircle) {
		userAccuracyCircle.setLatLng([lat, lng]).setRadius(accuracy);
	} else {
		userAccuracyCircle = L.circle([lat, lng], {
			radius: accuracy,
			color: "#2196f3",
			fillColor: "#2196f3",
			fillOpacity: 0.1,
			weight: 1,
		}).addTo(map);
	}
}

function fetchNearestShelters(lat, lng) {
	fetch(`api/shelters/nearest?lat=${lat}&lng=${lng}`)
		.then((response) => {
			return response.json();
		})
		.then((nearestShelters) => {
			var listEl = document.querySelector("#shelterList");
			listEl.innerHTML = "";

			if (nearestShelters.length === 0) {
				listEl.innerHTML =
					'<p class="empty-state">No shelters found in the area.</p>';
				return;
			}

			nearestShelters.forEach((s) => {
				var dist =
					s.distance_meters < 1000
						? `${s.distance_meters} m`
						: `${(s.distance_meters / 1000).toFixed(1)} km`;
				var div = document.createElement("div");
				div.className = "shelter-item";
				div.setAttribute("data-lat", s.latitude);
				div.setAttribute("data-lng", s.longitude);
				div.innerHTML = `<strong>${s.name}</strong><span class="badge badge-status-${s.status}">${s.status}</span><span class="badge badge-type-${s.shelter_type}">${s.shelter_type}</span><small>${s.address}</small><small>Distance: ${dist}</small><small>Capacity: ${s.current_occupancy} / ${s.capacity}</small>`;
				listEl.appendChild(div);
			});
		});
}

function clearRoutes() {
	routeLayers.forEach((layer) => {
		map.removeLayer(layer);
	});
	routeLayers = [];
}

function fetchNearestRoutes(lat, lng) {
	fetch(`api/routes/nearest?lat=${lat}&lng=${lng}`)
		.then((response) => response.json())
		.then((routes) => {
			clearRoutes();

			routes.forEach((route) => {
				if (!route.route_geometry || route.route_geometry.length === 0) return;

				var polyline = L.polyline(route.route_geometry, {
					color: route.status === "blocked" ? "#d32f2f" : "#4caf50",
					weight: 4,
					opacity: 0.8,
					dashArray: route.status === "blocked" ? "8, 8" : null,
				}).addTo(map);

				polyline.bindPopup(
					`<strong>${route.name}</strong><br>Route to: ${route.shelter_name}<br>Duration: ~${route.estimated_minutes} min<br>Distance: ${route.distance_meters} m<br>Status: <span style="color:${route.status === "blocked" ? "#d32f2f" : "#4caf50"};">${route.status}</span>`,
				);

				routeLayers.push(polyline);
			});

			var routeListEl = document.querySelector("#routeList");
			if (routeListEl) {
				routeListEl.innerHTML = "";

				if (routes.length === 0) {
					routeListEl.innerHTML =
						'<p class="empty-state">No evacuation routes found in the area.</p>';
					return;
				}

				routes.forEach((route) => {
					var dist =
						route.distance_from_point < 1000
							? `${route.distance_from_point} m`
							: `${(route.distance_from_point / 1000).toFixed(1)} km`;
					var div = document.createElement("div");
					div.className = "route-item";
					div.setAttribute("data-route-id", route.id);
					div.innerHTML = `<strong>${route.name}</strong><span class="badge badge-status-${route.status}">${route.status}</span><small>To: ${route.shelter_name}</small><small>Duration: ~${route.estimated_minutes} min | ${dist}</small>`;
					routeListEl.appendChild(div);
				});
			}
		});
}

function onLocationFound(position) {
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
	var accuracy = position.coords.accuracy;

	showLocationBanner("Location found.", false);
	setTimeout(hideLocationBanner, 3000);

	updateUserMarker(lat, lng, accuracy);
	fetchNearestShelters(lat, lng);
	fetchNearestRoutes(lat, lng);
}

function onLocationError(error) {
	showLocationBanner(`Could not obtain location: ${error.message}`, true);
}

showLocationSpinner();

if (navigator.geolocation) {
	navigator.geolocation.getCurrentPosition(onLocationFound, onLocationError, {
		enableHighAccuracy: true,
		timeout: 10000,
		maximumAge: 300000,
	});

	navigator.geolocation.watchPosition(onLocationFound, () => {}, {
		enableHighAccuracy: true,
		maximumAge: 60000,
	});
} else {
	showLocationBanner("Geolocation is not supported by this browser.", true);
}

document.querySelector("#locateBtn").addEventListener("click", () => {
	if (!navigator.geolocation) {
		showLocationBanner("Geolocation is not supported by this browser.", true);
		return;
	}

	showLocationSpinner();

	navigator.geolocation.getCurrentPosition(
		(position) => {
			var lat = position.coords.latitude;
			var lng = position.coords.longitude;
			var accuracy = position.coords.accuracy;

			showLocationBanner("Location found.", false);
			setTimeout(hideLocationBanner, 3000);

			updateUserMarker(lat, lng, accuracy);
			map.setView([lat, lng], 14);
			fetchNearestShelters(lat, lng);
			fetchNearestRoutes(lat, lng);
		},
		(error) => {
			showLocationBanner(`Could not obtain location: ${error.message}`, true);
		},
		{ enableHighAccuracy: true, timeout: 10000 },
	);
});

document.addEventListener("click", (e) => {
	var item = e.target.closest(".event-item, .shelter-item");
	if (!item) return;

	var lat = parseFloat(item.getAttribute("data-lat"));
	var lng = parseFloat(item.getAttribute("data-lng"));

	if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
		map.setView([lat, lng], 15);
	}
});
