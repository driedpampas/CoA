var map = L.map("map").setView([47.1622, 27.5889], 13);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
	attribution:
		'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
	maxZoom: 19,
}).addTo(map);

var eventsLayer = L.layerGroup().addTo(map);
var sheltersLayer = L.layerGroup().addTo(map);
var userLocationLayer = L.layerGroup().addTo(map);
var routesLayer = L.layerGroup().addTo(map);

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

eventsData.forEach((event) => {
	if (!event.latitude || !event.longitude) return;

	var color = getMarkerColor(event.event_type);
	var marker = L.marker([event.latitude, event.longitude], {
		icon: getMarkerIcon(color),
	}).addTo(eventsLayer);

	marker.bindPopup(
		`<strong>${event.title}</strong><br><span class="badge badge-${event.event_type}" style="font-size:11px;">${event.event_type}</span><br>Severity: ${event.severity}<br><small>${event.started_at}</small><br><p style='margin-top:6px;font-size:12px;'>${(event.description || "").substring(0, 150)}</p>`,
	);
});

sheltersData.forEach((shelter) => {
	if (!shelter.latitude || !shelter.longitude) return;

	var marker = L.marker([shelter.latitude, shelter.longitude], {
		icon: shelterIcon,
	}).addTo(sheltersLayer);

	marker.bindPopup(
		`<strong>${shelter.name}</strong><br><span style="color:#4caf50;font-weight:600;">SHELTER</span> <span style="font-size:11px;color:#666;">${shelter.shelter_type}</span><br>${shelter.address}<br>Capacity: ${shelter.current_occupancy} / ${shelter.capacity}<br>Status: ${shelter.status}${shelter.contact_phone ? `<br>Phone: ${shelter.contact_phone}` : ""}`,
	);
});

var userMarker = null;
var userAccuracyCircle = null;
var userLat = null;
var userLng = null;
var locationBannerEl = document.querySelector("#locationBanner");
var locationBannerTextEl = document.querySelector("#locationBannerText");
var hideBannerTimeout = null;

function showLocationBanner(text, isError) {
	if (!locationBannerEl) return;
	clearTimeout(hideBannerTimeout);
	console.log("[Location]", isError ? "ERROR:" : "INFO:", text);
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
	console.log("[Location] Hiding banner");
	console.trace();
	locationBannerEl.classList.add("location-banner--hidden");
}

function showLocationSpinner() {
	if (!locationBannerEl) return;
	clearTimeout(hideBannerTimeout);
	console.log("[Location] Obtaining location...");
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
	html: '<div style="position:relative;width:28px;height:28px;"><div style="position:absolute;top:0;left:0;width:28px;height:28px;background:rgba(33,150,243,0.3);border-radius:50%;animation:pulse 2s infinite;"></div><div style="position:absolute;top:5px;left:5px;width:18px;height:18px;background:#2196f3;border-radius:50%;border:3px solid #fff;box-shadow:0 0 8px rgba(33,150,243,0.6),0 1px 4px rgba(0,0,0,0.3);"></div></div>',
	iconSize: [28, 28],
	iconAnchor: [14, 14],
});

function updateUserMarker(lat, lng, accuracy) {
	if (userMarker) {
		userMarker.setLatLng([lat, lng]);
	} else {
		userMarker = L.marker([lat, lng], { icon: userLocationIcon })
			.addTo(userLocationLayer)
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
		}).addTo(userLocationLayer);
	}
}

function fetchNearestShelters(lat, lng) {
	fetch(`api/shelters/nearest?lat=${lat}&lng=${lng}`)
		.then((response) => response.json())
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
	routesLayer.clearLayers();
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
				}).addTo(routesLayer);

				polyline.bindPopup(
					`<strong>${route.name}</strong><br>Route to: ${route.shelter_name}<br>Duration: ~${route.estimated_minutes} min<br>Distance: ${route.distance_meters} m<br>Status: <span style="color:${route.status === "blocked" ? "#d32f2f" : "#4caf50"};">${route.status}</span>`,
				);
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

	console.log(
		"[Location] Found:",
		lat,
		lng,
		"| accuracy:",
		`${accuracy}m`,
		"| timestamp:",
		new Date(position.timestamp).toISOString(),
	);

	userLat = lat;
	userLng = lng;

	showLocationBanner("Location found.", false);
	hideBannerTimeout = setTimeout(hideLocationBanner, 3000);

	updateUserMarker(lat, lng, accuracy);
	fetchNearestShelters(lat, lng);
	fetchNearestRoutes(lat, lng);
}

function onLocationError(error) {
	console.error(
		"[Location] Error code:",
		error.code,
		"| message:",
		error.message,
	);
	showLocationBanner(`Could not obtain location: ${error.message}`, true);
}

showLocationSpinner();

if (navigator.geolocation) {
	console.log("[Location] Requesting initial position (getCurrentPosition)...");
	navigator.geolocation.getCurrentPosition(onLocationFound, onLocationError, {
		enableHighAccuracy: true,
		timeout: 10000,
		maximumAge: 300000,
	});

	console.log("[Location] Starting watchPosition...");
	navigator.geolocation.watchPosition(
		(position) => {
			var lat = position.coords.latitude;
			var lng = position.coords.longitude;
			var accuracy = position.coords.accuracy;

			console.log(
				"[Location] watchPosition update:",
				lat,
				lng,
				"| accuracy:",
				`${accuracy}m`,
			);

			userLat = lat;
			userLng = lng;

			updateUserMarker(lat, lng, accuracy);
			fetchNearestShelters(lat, lng);
			fetchNearestRoutes(lat, lng);
		},
		(error) => {
			console.warn(
				"[Location] watchPosition error:",
				error.code,
				error.message,
			);
		},
		{ enableHighAccuracy: true, maximumAge: 60000 },
	);
} else {
	console.error("[Location] Geolocation not supported by browser");
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

			console.log(
				"[Location] Manual locate:",
				lat,
				lng,
				"| accuracy:",
				`${accuracy}m`,
			);

			userLat = lat;
			userLng = lng;

			showLocationBanner("Location found.", false);
			hideBannerTimeout = setTimeout(hideLocationBanner, 3000);

			updateUserMarker(lat, lng, accuracy);
			map.setView([lat, lng], 14);
			fetchNearestShelters(lat, lng);
			fetchNearestRoutes(lat, lng);
		},
		(error) => {
			console.error(
				"[Location] Manual locate error:",
				error.code,
				error.message,
			);
			showLocationBanner(`Could not obtain location: ${error.message}`, true);
		},
		{ enableHighAccuracy: true, timeout: 10000 },
	);
});

document.querySelector("#centerOnMe").addEventListener("click", () => {
	if (userLat !== null && userLng !== null) {
		map.setView([userLat, userLng], 15);
	} else if (navigator.geolocation) {
		showLocationSpinner();
		navigator.geolocation.getCurrentPosition(
			(position) => {
				var lat = position.coords.latitude;
				var lng = position.coords.longitude;
				var accuracy = position.coords.accuracy;
				userLat = lat;
				userLng = lng;
				updateUserMarker(lat, lng, accuracy);
				map.setView([lat, lng], 15);
				showLocationBanner("Location found.", false);
				hideBannerTimeout = setTimeout(hideLocationBanner, 3000);
			},
			(error) => {
				console.error(
					"[Location] Center on me error:",
					error.code,
					error.message,
				);
				showLocationBanner(`Could not obtain location: ${error.message}`, true);
			},
			{ enableHighAccuracy: true, timeout: 10000 },
		);
	}
});

document.querySelector("#toggleEvents").addEventListener("change", (e) => {
	if (e.target.checked) {
		eventsLayer.addTo(map);
	} else {
		map.removeLayer(eventsLayer);
	}
});

document.querySelector("#toggleShelters").addEventListener("change", (e) => {
	if (e.target.checked) {
		sheltersLayer.addTo(map);
	} else {
		map.removeLayer(sheltersLayer);
	}
});

document.querySelector("#toggleUser").addEventListener("change", (e) => {
	if (e.target.checked) {
		userLocationLayer.addTo(map);
	} else {
		map.removeLayer(userLocationLayer);
	}
});

document.querySelector("#toggleRoutes").addEventListener("change", (e) => {
	if (e.target.checked) {
		routesLayer.addTo(map);
	} else {
		map.removeLayer(routesLayer);
	}
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

var menuToggle = document.getElementById("menuToggle");
var headerNav = document.getElementById("headerNav");

if (menuToggle && headerNav) {
	menuToggle.addEventListener("click", () => {
		menuToggle.classList.toggle("open");
		headerNav.classList.toggle("open");
	});

	headerNav.addEventListener("click", (e) => {
		if (e.target.tagName === "A") {
			menuToggle.classList.remove("open");
			headerNav.classList.remove("open");
		}
	});
}

var notificationBell = document.getElementById("notificationBell");
var notificationDropdown = document.getElementById("notificationDropdown");
var notificationList = document.getElementById("notificationList");
var notificationBadge = document.getElementById("notificationBadge");
var markAllReadBtn = document.getElementById("markAllRead");

function updateBadge(count) {
	if (!notificationBadge) return;
	notificationBadge.textContent = count;
	if (count > 0) {
		notificationBadge.classList.remove("hidden");
	} else {
		notificationBadge.classList.add("hidden");
	}
}

function formatTimeAgo(dateStr) {
	var date = new Date(dateStr.replace(" ", "T") + "Z");
	var now = new Date();
	var diff = Math.floor((now - date) / 1000);
	if (diff < 60) return "just now";
	if (diff < 3600) return Math.floor(diff / 60) + "m ago";
	if (diff < 86400) return Math.floor(diff / 3600) + "h ago";
	return Math.floor(diff / 86400) + "d ago";
}

function renderNotifications(notifications) {
	if (!notificationList) return;

	if (!notifications || notifications.length === 0) {
		notificationList.innerHTML = '<p class="empty-state">No notifications.</p>';
		return;
	}

	notificationList.innerHTML = "";
	notifications.forEach(function(n) {
		var div = document.createElement("div");
		div.className = "notification-item" + (n.is_read === "0" || n.is_read === 0 ? " unread" : "");
		div.setAttribute("data-id", n.id);
		div.innerHTML =
			'<div class="notification-item-title">' + n.title + '</div>' +
			'<div class="notification-item-message">' + n.message + '</div>' +
			'<div class="notification-item-meta">' +
			'<span class="notification-severity notification-severity-' + n.severity + '">' + n.severity + '</span>' +
			'<span>' + formatTimeAgo(n.created_at) + '</span>' +
			'</div>';
		notificationList.appendChild(div);
	});
}

function fetchNotifications() {
	fetch("api/notifications")
		.then(function(r) { return r.json(); })
		.then(function(data) {
			renderNotifications(data);
		});
}

function fetchUnreadCount() {
	fetch("api/notifications/unread-count")
		.then(function(r) { return r.json(); })
		.then(function(data) {
			updateBadge(data.count || 0);
		});
}

if (notificationBell && notificationDropdown) {
	notificationBell.addEventListener("click", function(e) {
		e.stopPropagation();
		var isHidden = notificationDropdown.classList.contains("hidden");
		notificationDropdown.classList.toggle("hidden");
		if (isHidden) {
			fetchNotifications();
		}
	});

	document.addEventListener("click", function(e) {
		if (!notificationDropdown.contains(e.target) && e.target !== notificationBell && !notificationBell.contains(e.target)) {
			notificationDropdown.classList.add("hidden");
		}
	});

	if (markAllReadBtn) {
		markAllReadBtn.addEventListener("click", function() {
			fetch("api/notifications/read-all", { method: "POST" })
				.then(function(r) { return r.json(); })
				.then(function() {
					updateBadge(0);
					fetchNotifications();
				});
		});
	}

	if (notificationList) {
		notificationList.addEventListener("click", function(e) {
			var item = e.target.closest(".notification-item");
			if (!item) return;
			var id = item.getAttribute("data-id");
			if (!id) return;

			fetch("api/notifications/" + id + "/read", { method: "POST" })
				.then(function(r) { return r.json(); })
				.then(function() {
					item.classList.remove("unread");
					fetchUnreadCount();
				});
		});
	}

	setInterval(fetchUnreadCount, 60000);
}
