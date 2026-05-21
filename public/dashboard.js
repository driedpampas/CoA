var map = L.map("map").setView([44.4268, 26.1025], 12);

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
		html:
			'<div style="background:' +
			color +
			';width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.4);"></div>',
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

eventsData.forEach(function (event) {
	if (!event.latitude || !event.longitude) return;

	var color = getMarkerColor(event.event_type);
	var marker = L.marker([event.latitude, event.longitude], {
		icon: getMarkerIcon(color),
	}).addTo(map);

	marker.bindPopup(
		"<strong>" +
			event.title +
			"</strong><br>" +
			'<span class="badge badge-' +
			event.event_type +
			'" style="font-size:11px;">' +
			event.event_type +
			"</span><br>" +
			"Severity: " +
			event.severity +
			"<br>" +
			"<small>" +
			event.started_at +
			"</small><br>" +
			"<p style='margin-top:6px;font-size:12px;'>" +
			(event.description || "").substring(0, 150) +
			"</p>",
	);

	eventMarkers.push(marker);
});

sheltersData.forEach(function (shelter) {
	if (!shelter.latitude || !shelter.longitude) return;

	var marker = L.marker([shelter.latitude, shelter.longitude], {
		icon: shelterIcon,
	}).addTo(map);

	marker.bindPopup(
		"<strong>" +
			shelter.name +
			"</strong><br>" +
			'<span style="color:#4caf50;font-weight:600;">SHELTER</span><br>' +
			shelter.address +
			"<br>" +
			"Capacity: " +
			shelter.current_occupancy +
			" / " +
			shelter.capacity +
			"<br>" +
			"Status: " +
			shelter.status +
			(shelter.contact_phone ? "<br>Phone: " + shelter.contact_phone : ""),
	);

	shelterMarkers.push(marker);
});

var userMarker = null;
var userAccuracyCircle = null;

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
			.bindPopup("Locatia ta");
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
	fetch("api/shelters/nearest?lat=" + lat + "&lng=" + lng)
		.then(function (response) {
			return response.json();
		})
		.then(function (nearestShelters) {
			var listEl = document.querySelector("#shelterList");
			listEl.innerHTML = "";

			if (nearestShelters.length === 0) {
				listEl.innerHTML =
					'<p class="empty-state">Nu s-au gasit adaposturi in zona.</p>';
				return;
			}

			nearestShelters.forEach(function (s) {
				var dist =
					s.distance_meters < 1000
						? s.distance_meters + " m"
						: (s.distance_meters / 1000).toFixed(1) + " km";
				var div = document.createElement("div");
				div.className = "shelter-item";
				div.setAttribute("data-lat", s.latitude);
				div.setAttribute("data-lng", s.longitude);
				div.innerHTML =
					"<strong>" +
					s.name +
					"</strong>" +
					'<span class="badge badge-status-' +
					s.status +
					'">' +
					s.status +
					"</span>" +
					"<small>" +
					s.address +
					"</small>" +
					"<small>Distanța: " +
					dist +
					"</small>" +
					"<small>Capacitate: " +
					s.current_occupancy +
					" / " +
					s.capacity +
					"</small>";
				listEl.appendChild(div);
			});
		});
}

function onLocationFound(position) {
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
	var accuracy = position.coords.accuracy;
	var statusEl = document.querySelector("#locationStatus");

	statusEl.textContent = "Locatie gasita.";

	updateUserMarker(lat, lng, accuracy);
	fetchNearestShelters(lat, lng);
}

function onLocationError(error) {
	var statusEl = document.querySelector("#locationStatus");
	statusEl.textContent = "Nu s-a putut obtine locatia: " + error.message;
}

if (navigator.geolocation) {
	navigator.geolocation.getCurrentPosition(onLocationFound, onLocationError, {
		enableHighAccuracy: true,
		timeout: 10000,
		maximumAge: 300000,
	});

	var watchId = navigator.geolocation.watchPosition(
		onLocationFound,
		function () {},
		{ enableHighAccuracy: true, maximumAge: 60000 },
	);
}

document.querySelector("#locateBtn").addEventListener("click", function () {
	var statusEl = document.querySelector("#locationStatus");

	if (!navigator.geolocation) {
		statusEl.textContent = "Geolocation not supported.";
		return;
	}

	statusEl.textContent = "Se cauta locatia...";

	navigator.geolocation.getCurrentPosition(
		function (position) {
			var lat = position.coords.latitude;
			var lng = position.coords.longitude;
			var accuracy = position.coords.accuracy;

			statusEl.textContent = "Locatie gasita.";
			updateUserMarker(lat, lng, accuracy);
			map.setView([lat, lng], 14);
			fetchNearestShelters(lat, lng);
		},
		function (error) {
			statusEl.textContent = "Nu s-a putut obtine locatia: " + error.message;
		},
		{ enableHighAccuracy: true, timeout: 10000 },
	);
});

document.addEventListener("click", function (e) {
	var item = e.target.closest(".event-item, .shelter-item");
	if (!item) return;

	var lat = parseFloat(item.getAttribute("data-lat"));
	var lng = parseFloat(item.getAttribute("data-lng"));

	if (!isNaN(lat) && !isNaN(lng)) {
		map.setView([lat, lng], 15);
	}
});
