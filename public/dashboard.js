var mapEl = document.getElementById("map");

setTimeout(function () {
	if (!mapEl) return;

	var map = L.map("map").setView([47.1835, 27.5644], 14);

	L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
		attribution:
			'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
		maxZoom: 19,
	}).addTo(map);

	var mapInvalidateTimeout = null;
	function debouncedInvalidateSize() {
		if (mapInvalidateTimeout) return;
		mapInvalidateTimeout = setTimeout(function () {
			mapInvalidateTimeout = null;
			if (map) {
				map.invalidateSize();
			}
		}, 100);
	}

	if (window.ResizeObserver) {
		new ResizeObserver(function () {
			debouncedInvalidateSize();
		}).observe(mapEl);
	} else {
		window.addEventListener("resize", debouncedInvalidateSize);
		window.addEventListener("orientationchange", debouncedInvalidateSize);

		if (window.MutationObserver) {
			new MutationObserver(function () {
				debouncedInvalidateSize();
			}).observe(mapEl, {
				attributes: true,
				attributeFilter: ["style", "class"],
			});
		}
	}

	var eventsLayer = L.layerGroup().addTo(map);
	var sheltersLayer = L.layerGroup().addTo(map);
	var userLocationLayer = L.layerGroup().addTo(map);
	var routesLayer = L.layerGroup().addTo(map);
	var osrmRouteLayer = null;
	var favoriteRouteLayer = null;
	var profilePreferredShelterId = typeof window.profilePreferredShelterId !== "undefined" ? window.profilePreferredShelterId : null;
	var mapEventsData = Array.isArray(eventsData) ? eventsData.slice() : [];
	var mapEventWindowStorageKey = "coa-map-event-window-days";
	var mapEventWindowInput = document.querySelector("#eventWindowDays");

	var MOCK_LAT = 47.1835;
	var MOCK_LNG = 27.5644;
	var PROXIMITY_RADIUS_KM = 25;
	var serverNotificationsData = [];
	var serverUnreadCount = 0;
	var liveNotificationsStorageKey =
		currentUserId !== null ? `coa-live-notifications-${currentUserId}` : null;
	var liveNotificationsData = getNotificationStorage();

	var profileRadius = typeof window.profileRadius !== "undefined" ? window.profileRadius : null;

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

	function clampMapEventWindowDays(value) {
		var days = parseInt(value, 10);
		if (Number.isNaN(days)) days = 1;
		if (days < 1) days = 1;
		if (days > 30) days = 30;
		return days;
	}

	function loadMapEventWindowDays() {
		var days = 1;

		try {
			var stored = localStorage.getItem(mapEventWindowStorageKey);
			days = stored !== null ? clampMapEventWindowDays(stored) : clampMapEventWindowDays(mapEventWindowInput ? mapEventWindowInput.value : 1);
		} catch (err) {
			days = clampMapEventWindowDays(mapEventWindowInput ? mapEventWindowInput.value : 1);
		}

		if (mapEventWindowInput) {
			mapEventWindowInput.value = days;
		}

		return days;
	}

	function saveMapEventWindowDays(days) {
		try {
			localStorage.setItem(mapEventWindowStorageKey, String(days));
		} catch (err) {
			console.warn("[Events] Failed to persist map window:", err);
		}
	}

	var shelterIcon = L.divIcon({
		className: "custom-marker",
		html: '<div style="background:#4caf50;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.4);"></div>',
		iconSize: [14, 14],
		iconAnchor: [7, 7],
	});

	function renderEventsOnMap() {
		eventsLayer.clearLayers();
		mapEventsData.forEach((event) => {
			if (!event.latitude || !event.longitude) return;

			if (profileRadius !== null && profileRadius > 0 && userLat !== null && userLng !== null) {
				var dist = haversineDistance(userLat, userLng, parseFloat(event.latitude), parseFloat(event.longitude));
				if (dist > profileRadius) return;
			}

			var color = getMarkerColor(event.event_type);
			var marker = L.marker([event.latitude, event.longitude], {
				icon: getMarkerIcon(color),
			}).addTo(eventsLayer);

			marker.bindPopup(
				`<strong>${event.title}</strong><br><span class="badge badge-${event.event_type}" style="font-size:11px;">${event.event_type}</span><br>Severity: ${event.severity}<br><small>${event.started_at}</small><br><p style='margin-top:6px;font-size:12px;'>${(event.description || "").substring(0, 150)}</p>`,
			);
		});
	}

	function fetchMapEvents() {
		var days = loadMapEventWindowDays();
		saveMapEventWindowDays(days);

		fetch(`api/events?days=${days}`)
			.then((r) => r.json())
			.then((data) => {
				mapEventsData = Array.isArray(data) ? data : [];
				renderEventsOnMap();
			})
			.catch((err) => {
				console.warn("[Map] Failed to fetch recent events:", err);
			});
	}

	loadMapEventWindowDays();
	renderEventsOnMap();
	fetchMapEvents();

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
		locationBannerEl.classList.add("location-banner--hidden");
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
					div.innerHTML = `<strong>${s.name}</strong><span class='badge badge-status-${s.status}'>${s.status}</span><span class='badge badge-type-${s.shelter_type}'>${s.shelter_type}</span><small>${s.address}</small><small>Distance: ${dist}</small><small>Capacity: ${s.current_occupancy} / ${s.capacity}</small>`;
					listEl.appendChild(div);
				});
			});
	}

	function clearRoutes() {
		routesLayer.clearLayers();
		if (osrmRouteLayer) {
			routesLayer.addLayer(osrmRouteLayer);
		}
		if (favoriteRouteLayer) {
			routesLayer.addLayer(favoriteRouteLayer);
		}
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
						`<strong>${route.name}</strong><br>Route to: ${route.shelter_name}<br>Duration: ~${route.estimated_minutes} min<br>Distance: ${route.distance_meters} m<br>Status: <span style='color:${route.status === "blocked" ? "#d32f2f" : "#4caf50"};'>${route.status}</span>`,
					);
				});

				var routeListEl = document.querySelector("#routeList");
				if (routeListEl) {
					routeListEl.innerHTML = "";

					if (routes.length === 0) {
						routeListEl.innerHTML =
							'<p id="routesEmptyState" class="empty-state">No evacuation routes found in the area.</p>';
					} else {
						routes.forEach((route) => {
							var dist =
								route.distance_from_point < 1000
									? `${route.distance_from_point} m`
									: `${(route.distance_from_point / 1000).toFixed(1)} km`;
							var div = document.createElement("div");
							div.className = `route-item status-${route.status}`;
							div.setAttribute("data-route-id", route.id);
							if (route.route_geometry && route.route_geometry.length > 0) {
								div.setAttribute("data-lat", route.route_geometry[0][0]);
								div.setAttribute("data-lng", route.route_geometry[0][1]);
							}
							div.innerHTML = `
								<div class="route-header">
									<strong>${route.name}</strong>
									<span class="badge badge-status-${route.status}">${route.status}</span>
								</div>
								<div class="route-meta">
									<div class="route-meta-row">
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
											<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
											<circle cx="12" cy="10" r="3"/>
										</svg>
										<span>To: <span class="route-destination">${route.shelter_name}</span></span>
									</div>
									<div class="route-meta-row">
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
											<circle cx="12" cy="12" r="10"/>
											<polyline points="12 6 12 12 16 14"/>
										</svg>
										<span>~${route.estimated_minutes} min</span>
										<span class="bullet-separator">•</span>
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
											<polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
											<line x1="9" y1="3" x2="9" y2="18"/>
											<line x1="15" y1="6" x2="15" y2="21"/>
										</svg>
										<span>${dist}</span>
									</div>
								</div>
							`;
							routeListEl.appendChild(div);
						});
					}
				}

				fetchFavoriteRoute(lat, lng);
			});
	}

	function fetchFavoriteRoute(lat, lng) {
		if (favoriteRouteLayer) {
			routesLayer.removeLayer(favoriteRouteLayer);
			favoriteRouteLayer = null;
		}

		var routeListEl = document.querySelector("#routeList");
		if (routeListEl) {
			var existingItem = routeListEl.querySelector(".route-item.favorite-route");
			if (existingItem) {
				existingItem.remove();
			}
		}

		if (!emergencyMode) return;

		if (!profilePreferredShelterId) return;

		var favShelter = sheltersData.find(function (s) {
			return parseInt(s.id) === parseInt(profilePreferredShelterId);
		});

		if (!favShelter || !favShelter.latitude || !favShelter.longitude) return;

		var toLat = parseFloat(favShelter.latitude);
		var toLng = parseFloat(favShelter.longitude);

		var url =
			"https://router.project-osrm.org/route/v1/driving/" +
			lng +
			"," +
			lat +
			";" +
			toLng +
			"," +
			toLat +
			"?overview=full&geometries=geojson&steps=true";

		fetch(url)
			.then((r) => r.json())
			.then((data) => {
				if (data.routes && data.routes.length > 0) {
					displayFavoriteRoute(data.routes[0], favShelter);
				}
			})
			.catch((err) => {
				console.error("[OSRM] Favorite route fetch failed:", err);
			});
	}

	function displayFavoriteRoute(route, shelter) {
		if (favoriteRouteLayer) {
			routesLayer.removeLayer(favoriteRouteLayer);
		}

		var coords = route.geometry.coordinates.map((c) => {
			return [c[1], c[0]];
		});

		var durationMin = Math.round(route.duration / 60);
		var dist = route.distance;
		var distStr =
			dist < 1000
				? `${dist.toFixed(0)} m`
				: `${(dist / 1000).toFixed(1)} km`;

		favoriteRouteLayer = L.polyline(coords, {
			color: "#8b5cf6",
			weight: 5,
			opacity: 0.9,
		}).addTo(routesLayer);

		favoriteRouteLayer.bindPopup(
			`<strong>Favorite Shelter Route (Autogenerated)</strong><br>Route to: ${shelter.name}<br>Duration: ~${durationMin} min<br>Distance: ${distStr}`,
		);

		var routeListEl = document.querySelector("#routeList");
		if (routeListEl) {
			var emptyState = routeListEl.querySelector(".empty-state");
			if (emptyState) {
				emptyState.remove();
			}

			var existingItem = routeListEl.querySelector(".route-item.favorite-route");
			if (existingItem) {
				existingItem.remove();
			}

			var div = document.createElement("div");
			div.className = "route-item status-active favorite-route";
			div.setAttribute("data-lat", coords[0][0]);
			div.setAttribute("data-lng", coords[0][1]);
			div.innerHTML = `
				<div class="route-header">
					<strong>Route to Favorite Shelter</strong>
					<span class="badge badge-status-favorite">Favorite</span>
				</div>
				<div class="route-meta">
					<div class="route-meta-row">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
							<circle cx="12" cy="10" r="3"/>
						</svg>
						<span>To: <span class="route-destination">${shelter.name}</span></span>
					</div>
					<div class="route-meta-row">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
							<circle cx="12" cy="12" r="10"/>
							<polyline points="12 6 12 12 16 14"/>
						</svg>
						<span>~${durationMin} min</span>
						<span class="bullet-separator">•</span>
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
							<polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
							<line x1="9" y1="3" x2="9" y2="18"/>
							<line x1="15" y1="6" x2="15" y2="21"/>
						</svg>
						<span>${distStr}</span>
					</div>
				</div>
			`;

			routeListEl.insertBefore(div, routeListEl.firstChild);
		}
	}

	function haversineDistance(lat1, lng1, lat2, lng2) {
		var R = 6371;
		var dLat = ((lat2 - lat1) * Math.PI) / 180;
		var dLng = ((lng2 - lng1) * Math.PI) / 180;
		var a =
			Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos((lat1 * Math.PI) / 180) *
			Math.cos((lat2 * Math.PI) / 180) *
			Math.sin(dLng / 2) *
			Math.sin(dLng / 2);
		var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		return R * c;
	}

	function coordinateDistance(lat1, lng1, lat2, lng2) {
		var dLat = lat2 - lat1;
		var dLng = lng2 - lng1;
		return Math.sqrt(dLat * dLat + dLng * dLng);
	}

	function findNearestShelter(lat, lng) {
		var nearest = null;
		var nearestDist = Infinity;
		sheltersData.forEach((shelter) => {
			if (!shelter.latitude || !shelter.longitude) return;
			var dist = haversineDistance(
				lat,
				lng,
				parseFloat(shelter.latitude),
				parseFloat(shelter.longitude),
			);
			if (dist < nearestDist) {
				nearestDist = dist;
				nearest = shelter;
			}
		});
		return nearest;
	}

	function fetchOSRMRoute(fromLat, fromLng, toLat, toLng) {
		var url =
			"https://router.project-osrm.org/route/v1/driving/" +
			fromLng +
			"," +
			fromLat +
			";" +
			toLng +
			"," +
			toLat +
			"?overview=full&geometries=geojson&steps=true";

		fetch(url)
			.then((r) => r.json())
			.then((data) => {
				if (data.routes && data.routes.length > 0) {
					displayOSRMRoute(data.routes[0]);
				}
			})
			.catch((err) => {
				console.error("[OSRM] Route fetch failed:", err);
			});
	}

	function displayOSRMRoute(route) {
		if (osrmRouteLayer) {
			map.removeLayer(osrmRouteLayer);
		}

		var coords = route.geometry.coordinates.map((c) => {
			return [c[1], c[0]];
		});

		var durationMin = Math.round(route.duration / 60);
		var distKm = (route.distance / 1000).toFixed(1);

		osrmRouteLayer = L.polyline(coords, {
			color: "#ff6f00",
			weight: 6,
			opacity: 0.85,
		}).addTo(routesLayer);

		osrmRouteLayer.bindPopup(
			"<strong>Evacuation Route (OSRM)</strong><br>" +
			"Distance: " +
			distKm +
			" km<br>Estimated time: ~" +
			durationMin +
			" min",
		);

		map.fitBounds(osrmRouteLayer.getBounds(), { padding: [50, 50] });
	}

	var proximityActive = false;

	function checkEventProximity() {
		if (!userLat || !userLng || !eventsData || eventsData.length === 0) return;

		var nearestEvent = null;
		var nearestDist = Infinity;

		eventsData.forEach((event) => {
			if (!event.latitude || !event.longitude) return;
			if (event.status !== "active") return;
			var dist = haversineDistance(
				userLat,
				userLng,
				parseFloat(event.latitude),
				parseFloat(event.longitude),
			);
			if (dist < nearestDist) {
				nearestDist = dist;
				nearestEvent = event;
			}
		});

		if (nearestEvent && nearestDist <= PROXIMITY_RADIUS_KM) {
			if (!proximityActive) {
				proximityActive = true;
				triggerProximityAlert(nearestEvent, nearestDist);
			}
		} else {
			if (proximityActive) {
				proximityActive = false;
				clearProximityAlert();
			}
		}
	}

	function triggerProximityAlert(event, distance) {
		console.log(
			"[Proximity] Alert triggered for:",
			event.title,
			"at",
			distance.toFixed(1),
			"km",
		);

		var header = document.querySelector(".dashboard-header");
		if (header) header.classList.add("header-alert");

		setEmergencyMode(true);

		map.setView([userLat, userLng], 13);

		var nearestShelter = findNearestShelter(userLat, userLng);
		if (nearestShelter) {
			fetchOSRMRoute(
				userLat,
				userLng,
				parseFloat(nearestShelter.latitude),
				parseFloat(nearestShelter.longitude),
			);
		}

		if (profilePreferredShelterId) {
			fetchFavoriteRoute(userLat, userLng);
		}
	}

	function getNotificationStorage() {
		if (!liveNotificationsStorageKey) return [];

		try {
			var raw = localStorage.getItem(liveNotificationsStorageKey);
			var parsed = raw ? JSON.parse(raw) : [];
			return Array.isArray(parsed) ? parsed : [];
		} catch (err) {
			console.warn("[Notifications] Failed to load live alerts:", err);
			return [];
		}
	}

	function saveNotificationStorage(notifications) {
		if (!liveNotificationsStorageKey) return;

		try {
			localStorage.setItem(liveNotificationsStorageKey, JSON.stringify(notifications));
		} catch (err) {
			console.warn("[Notifications] Failed to persist live alerts:", err);
		}
	}

	function formatLocalTimestamp(date) {
		return date.toISOString().slice(0, 19).replace("T", " ");
	}

	function mapSeverityToNotificationSeverity(severity) {
		if (severity === "extreme") return "critical";
		if (severity === "high") return "warning";
		if (severity === "moderate") return "warning";
		return "info";
	}

	function getNearbyEventDistance(event) {
		if (userLat === null || userLng === null) return null;
		if (!event || event.latitude === null || event.longitude === null) return null;

		return haversineDistance(
			userLat,
			userLng,
			parseFloat(event.latitude),
			parseFloat(event.longitude),
		);
	}

	function getLiveNotificationKey(event) {
		return event && event.id ? `event-${event.id}` : null;
	}

	function getLiveUnreadCount() {
		return liveNotificationsData.filter((notification) => notification.is_read === 0 || notification.is_read === "0").length;
	}

	function updateNotificationBadge() {
		if (!notificationBadge) return;
		var total = serverUnreadCount + getLiveUnreadCount();
		notificationBadge.textContent = total;
		if (total > 0) {
			notificationBadge.classList.remove("hidden");
		} else {
			notificationBadge.classList.add("hidden");
		}
	}

	function buildLiveNotification(event, distance) {
		return {
			id: `live-${event.id}`,
			source: "live",
			user_id: currentUserId,
			title: `${event.event_type} nearby`,
			message:
				`${event.title} happened within ${distance.toFixed(1)} units of your location.` +
				(event.description ? ` ${event.description}` : ""),
			type: "event",
			severity: mapSeverityToNotificationSeverity(event.severity),
			reference_id: event.id,
			is_read: 0,
			created_at: formatLocalTimestamp(new Date()),
		};
	}

	function syncLiveNotificationsFromEvents() {
		if (!isLoggedIn || userLat === null || userLng === null || !eventsData || eventsData.length === 0) {
			updateNotificationBadge();
			return;
		}

		var changed = false;
		var maxDist = profileRadius !== null && profileRadius > 0 ? profileRadius : PROXIMITY_RADIUS_KM;

		eventsData.forEach((event) => {
			if (!event || event.status !== "active") return;
			if (event.latitude === null || event.longitude === null) return;

			var distance = getNearbyEventDistance(event);
			if (distance === null || distance > maxDist) return;

			var eventKey = getLiveNotificationKey(event);
			if (!eventKey) return;

			var existing = liveNotificationsData.find((notification) => {
				return notification.reference_id === event.id || notification.id === `live-${event.id}`;
			});

			if (existing) return;

			liveNotificationsData.unshift(buildLiveNotification(event, distance));
			changed = true;
		});

		if (changed) {
			saveNotificationStorage(liveNotificationsData);
		}

		updateNotificationBadge();
	}

	function normalizeNotification(notification) {
		return {
			id: notification.id,
			source: notification.source || "server",
			user_id: notification.user_id || currentUserId,
			title: notification.title,
			message: notification.message,
			type: notification.type || "system",
			severity: notification.severity || "info",
			reference_id: notification.reference_id || null,
			is_read: notification.is_read,
			created_at: notification.created_at,
		};
	}

	function getCombinedNotifications() {
		var merged = [];
		var seenEventKeys = {};

		serverNotificationsData.forEach((notification) => {
			var normalized = normalizeNotification(notification);
			if (normalized.type === "event" && normalized.reference_id !== null) {
				seenEventKeys[`event-${normalized.reference_id}`] = true;
			}
			merged.push(normalized);
		});

		liveNotificationsData.forEach((notification) => {
			var liveKey = `event-${notification.reference_id}`;
			if (seenEventKeys[liveKey]) return;
			merged.push(normalizeNotification(notification));
		});

		merged.sort((a, b) => {
			return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
		});

		return merged;
	}

	function clearProximityAlert() {
		console.log("[Proximity] Alert cleared");

		var header = document.querySelector(".dashboard-header");
		if (header) header.classList.remove("header-alert");

		setEmergencyMode(false);

		if (osrmRouteLayer) {
			map.removeLayer(osrmRouteLayer);
			osrmRouteLayer = null;
		}

		if (favoriteRouteLayer) {
			routesLayer.removeLayer(favoriteRouteLayer);
			favoriteRouteLayer = null;
		}

		var routeListEl = document.querySelector("#routeList");
		if (routeListEl) {
			var existingItem = routeListEl.querySelector(".route-item.favorite-route");
			if (existingItem) {
				existingItem.remove();
			}
		}
	}

	function areEventListsEqual(list1, list2) {
		if (!list1 || !list2) return false;
		if (list1.length !== list2.length) return false;
		for (var i = 0; i < list1.length; i++) {
			var e1 = list1[i];
			var e2 = list2[i];
			if (
				e1.id !== e2.id ||
				e1.status !== e2.status ||
				e1.severity !== e2.severity ||
				e1.title !== e2.title ||
				e1.latitude !== e2.latitude ||
				e1.longitude !== e2.longitude ||
				e1.started_at !== e2.started_at ||
				e1.description !== e2.description
			) {
				return false;
			}
		}
		return true;
	}

	function escapeHtml(str) {
		if (typeof str !== "string") {
			return str === null || str === undefined ? "" : String(str);
		}
		return str
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}

	function renderEventsList(events) {
		var panel = document.querySelector("#panel-events");
		if (!panel) return;

		var filteredEvents = events;
		if (profileRadius !== null && profileRadius > 0 && userLat !== null && userLng !== null) {
			filteredEvents = events.filter((event) => {
				if (!event.latitude || !event.longitude) return false;
				var dist = haversineDistance(userLat, userLng, parseFloat(event.latitude), parseFloat(event.longitude));
				return dist <= profileRadius;
			});
		}

		var h2 = panel.querySelector("h2");
		panel.innerHTML = "";
		if (h2) {
			panel.appendChild(h2);
		} else {
			var newH2 = document.createElement("h2");
			newH2.textContent = "Active Events";
			panel.appendChild(newH2);
		}

		if (!filteredEvents || filteredEvents.length === 0) {
			var p = document.createElement("p");
			p.className = "empty-state";
			p.textContent = "No active emergencies.";
			panel.appendChild(p);
			return;
		}

		var ul = document.createElement("ul");
		ul.className = "event-list";

		filteredEvents.forEach((event) => {
			var li = document.createElement("li");
			li.className = `event-item severity-${escapeHtml(event.severity || "moderate")}`;
			li.setAttribute("data-lat", event.latitude || "");
			li.setAttribute("data-lng", event.longitude || "");

			var desc = event.description || "";
			var truncatedDesc = desc.length > 100 ? desc.substring(0, 100) + "..." : desc;

			li.innerHTML = `
				<strong>${escapeHtml(event.title || "")}</strong>
				<span class="badge badge-${escapeHtml(event.event_type || "other")}">
					${escapeHtml(event.event_type || "other")}
				</span>
				<span class="badge badge-severity-${escapeHtml(event.severity || "moderate")}">
					${escapeHtml(event.severity || "moderate")}
				</span>
				<small>${escapeHtml(event.started_at || "")}</small>
				<p>${escapeHtml(truncatedDesc)}</p>
			`;
			ul.appendChild(li);
		});

		panel.appendChild(ul);
	}

	function pollEvents() {
		fetch("api/events")
			.then((r) => r.json())
			.then((data) => {
				var changed = !areEventListsEqual(eventsData, data);
				if (changed) {
					eventsData = data;
					renderEventsList(eventsData);
					fetchMapEvents();
					syncLiveNotificationsFromEvents();
					checkEventProximity();
				}
			})
			.catch((err) => {
				console.warn("[Poll] Failed to fetch events:", err);
			});
	}

	function setUserLocation(lat, lng) {
		userLat = lat;
		userLng = lng;
		updateUserMarker(lat, lng, 50);
		renderEventsList(eventsData);
		fetchNearestShelters(lat, lng);
		fetchNearestRoutes(lat, lng);
		syncLiveNotificationsFromEvents();
		checkEventProximity();
		pushUserLocation(lat, lng);
	}

	function pushUserLocation(lat, lng) {
		if (!isLoggedIn) return;

		fetch("api/auth/update-location", {
			method: "PATCH",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify({
				latitude: lat,
				longitude: lng,
			}),
		}).catch((err) => {
			console.warn("[Location] Failed to sync user location:", err);
		});
	}

	function initMockLocation() {
		console.log(
			`[Location] Using mock location: Copou Park (${MOCK_LAT}, ${MOCK_LNG})`,
		);
		setUserLocation(MOCK_LAT, MOCK_LNG);
		showLocationBanner("Mock location: Copou Park, Iasi", false);
		hideBannerTimeout = setTimeout(hideLocationBanner, 4000);
	}

	function initBrowserLocation(forceFallback) {
		if (!navigator.geolocation) {
			if (forceFallback) {
				initMockLocation();
			}
			return;
		}

		showLocationBanner("Detecting your location...", false);

		navigator.geolocation.getCurrentPosition(
			(position) => {
				setUserLocation(position.coords.latitude, position.coords.longitude);
				map.setView([position.coords.latitude, position.coords.longitude], 14);
				showLocationBanner("Live location detected", false);
				hideBannerTimeout = setTimeout(hideLocationBanner, 3000);
			},
			(error) => {
				console.warn("[Location] Browser geolocation failed:", error);
				if (forceFallback) {
					initMockLocation();
				}
			},
			{ enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
		);
	}

	initBrowserLocation(true);

	setInterval(pollEvents, 15000);

	document.querySelector("#locateBtn").addEventListener("click", () => {
		initBrowserLocation(true);
	});

	document.querySelector("#centerOnMe").addEventListener("click", () => {
		if (userLat !== null && userLng !== null) {
			map.setView([userLat, userLng], 15);
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

	var layerToggle = document.getElementById("layerToggle");
	var layerContent = document.getElementById("layerContent");
	if (layerToggle && layerContent) {
		layerToggle.addEventListener("click", function () {
			layerContent.classList.toggle("collapsed");
			layerToggle.textContent = layerContent.classList.contains("collapsed") ? "Layers ▴" : "Layers ▾";
			setTimeout(function () {
				if (map) map.invalidateSize();
			}, 120);
		});
	}

	if (mapEventWindowInput) {
		mapEventWindowInput.addEventListener("change", () => {
			var days = clampMapEventWindowDays(mapEventWindowInput.value);
			mapEventWindowInput.value = days;
			saveMapEventWindowDays(days);
			fetchMapEvents();
		});
	}

	document.addEventListener("click", (e) => {
		var item = e.target.closest(".event-item, .shelter-item, .route-item");
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

	var sidebarTabs = document.querySelectorAll(".sidebar-tab");
	var emergencyMode = false;

	function setEmergencyMode(active) {
		emergencyMode = active;
		var defaultTarget = active ? "shelterPanel" : "panel-events";
		var tabs = document.querySelectorAll(".sidebar-tab");
		if (!tabs.length) return;

		tabs.forEach((btn) => {
			var target = btn.getAttribute("data-target");
			btn.classList.toggle("active", target === defaultTarget);
		});

		["shelterPanel", "routesPanel", "panel-events"].forEach((id) => {
			var el = document.getElementById(id);
			if (!el) return;
			el.style.display = id === defaultTarget ? "" : "none";
		});

		if (map) {
			debouncedInvalidateSize();
		}
	}

	if (sidebarTabs.length) {
		sidebarTabs.forEach((btn) => {
			btn.addEventListener("click", () => {
				sidebarTabs.forEach((b) => b.classList.remove("active"));
				btn.classList.add("active");

				var targetId = btn.getAttribute("data-target");
				["shelterPanel", "routesPanel", "panel-events"].forEach((id) => {
					var el = document.getElementById(id);
					if (!el) return;
					el.style.display = id === targetId ? "" : "none";
				});

				if (map) {
					debouncedInvalidateSize();
				}
			});
		});
	}

	var notificationBell = document.getElementById("notificationBell");
	var notificationDropdown = document.getElementById("notificationDropdown");
	var notificationList = document.getElementById("notificationList");
	var notificationBadge = document.getElementById("notificationBadge");
	var markAllReadBtn = document.getElementById("markAllRead");

	function formatTimeAgo(dateStr) {
		var date = new Date(`${dateStr.replace(" ", "T")}Z`);
		var now = new Date();
		var diff = Math.floor((now - date) / 1000);
		if (diff < 60) return "just now";
		if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
		if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
		return `${Math.floor(diff / 86400)}d ago`;
	}

	function renderNotifications(notifications) {
		if (!notificationList) return;

		if (!notifications || notifications.length === 0) {
			notificationList.innerHTML = '<p class="empty-state">No notifications.</p>';
			return;
		}

		notificationList.innerHTML = "";
		notifications.forEach((n) => {
			var div = document.createElement("div");
			div.className = `notification-item${n.is_read === "0" || n.is_read === 0 ? " unread" : ""}`;
			div.setAttribute("data-id", n.id);
			div.setAttribute("data-source", n.source || "server");
			div.innerHTML =
				`<div class="notification-item-title">${n.title}</div>` +
				`<div class="notification-item-message">${n.message}</div>` +
				`<div class="notification-item-meta">` +
				`<span class="notification-severity notification-severity-${n.severity}">${n.severity}</span>` +
				`<span>${formatTimeAgo(n.created_at)}</span>` +
				`</div>`;
			notificationList.appendChild(div);
		});
	}

	function fetchNotifications() {
		fetch("api/notifications")
			.then((r) => r.json())
			.then((data) => {
				serverNotificationsData = Array.isArray(data) ? data : [];
				renderNotifications(getCombinedNotifications());
				updateNotificationBadge();
			});
	}

	function fetchUnreadCount() {
		fetch("api/notifications/unread-count")
			.then((r) => r.json())
			.then((data) => {
				serverUnreadCount = data.count || 0;
				updateNotificationBadge();
			});
	}

	updateNotificationBadge();
	fetchUnreadCount();
	syncLiveNotificationsFromEvents();

	if (notificationBell && notificationDropdown) {
		notificationBell.addEventListener("click", (e) => {
			e.stopPropagation();
			var isHidden = notificationDropdown.classList.contains("hidden");
			notificationDropdown.classList.toggle("hidden");
			if (isHidden) {
				fetchNotifications();
			}
		});

		document.addEventListener("click", (e) => {
			if (
				!notificationDropdown.contains(e.target) &&
				e.target !== notificationBell &&
				!notificationBell.contains(e.target)
			) {
				notificationDropdown.classList.add("hidden");
			}
		});

		if (markAllReadBtn) {
			markAllReadBtn.addEventListener("click", () => {
				fetch("api/notifications/read-all", { method: "PATCH" })
					.then((r) => r.json())
					.then(() => {
						serverUnreadCount = 0;
						liveNotificationsData = liveNotificationsData.map((notification) => {
							return Object.assign({}, notification, { is_read: 1 });
						});
						saveNotificationStorage(liveNotificationsData);
						renderNotifications(getCombinedNotifications());
						updateNotificationBadge();
					});
			});
		}

		if (notificationList) {
			notificationList.addEventListener("click", (e) => {
				var item = e.target.closest(".notification-item");
				if (!item) return;
				var id = item.getAttribute("data-id");
				if (!id) return;
				var source = item.getAttribute("data-source") || "server";

				if (source === "live") {
					liveNotificationsData = liveNotificationsData.map((notification) => {
						if (notification.id === id) {
							return Object.assign({}, notification, { is_read: 1 });
						}
						return notification;
					});
					saveNotificationStorage(liveNotificationsData);
					renderNotifications(getCombinedNotifications());
					updateNotificationBadge();
					return;
				}

				fetch(`api/notifications/${id}/read`, { method: "PATCH" })
					.then((r) => r.json())
					.then(() => {
						fetchNotifications();
						fetchUnreadCount();
					});
			});
		}

		setInterval(fetchUnreadCount, 60000);
	}
}, 50);
