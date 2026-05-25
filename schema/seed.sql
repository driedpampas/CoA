-- ============================================================
-- Sample data for COA Emergency Management System
-- Location: Iași, Romania
-- ============================================================

INSERT INTO shelters (name, address, latitude, longitude, geom_point, capacity, current_occupancy, shelter_type, status, contact_phone, notes) VALUES
('Sala Polivalenta', 'Strada Palat 5-7, Iasi', 47.1550, 27.5980, ST_GeomFromText('POINT(27.5980 47.1550)', 4326), 4000, 950, 'stadium', 'open', '0232-218-400', 'Large capacity, equipped with generators'),
('Stadionul CFR', 'Strada Calea Chisinaului 35, Iasi', 47.1630, 27.5750, ST_GeomFromText('POINT(27.5750 47.1630)', 4326), 6000, 2800, 'stadium', 'open', '0232-210-780', 'Main stadium, medical facilities available'),
('Liceul Costache Negri', 'Strada Costache Negri 29, Iasi', 47.1750, 27.5780, ST_GeomFromText('POINT(27.5780 47.1750)', 4326), 600, 180, 'school', 'open', '0232-253-012', 'Neighborhood shelter, water and food available'),
('Cercul Militar Iasi', 'Strada Stefan cel Mare 62, Iasi', 47.1600, 27.5820, ST_GeomFromText('POINT(27.5820 47.1600)', 4326), 350, 0, 'military', 'open', '0232-211-305', 'City center, easy to access'),
('Scoala Ion Heliade Radulescu', 'Strada Vasile Lupu 85, Iasi', 47.1480, 27.5700, ST_GeomFromText('POINT(27.5700 47.1480)', 4326), 500, 120, 'school', 'open', '0232-236-578', 'Nicolina area, medical assistance'),
('Complexul Studentesc Tudor Vladimirescu', 'Bulevardul Tudor Vladimirescu 50, Iasi', 47.1530, 27.6120, ST_GeomFromText('POINT(27.6120 47.1530)', 4326), 1500, 720, 'community', 'full', '0232-242-450', 'Full to maximum capacity'),
('Centrul Comunitar Alexandru cel Bun', 'Strada Sarbatorilor 10, Iasi', 47.1700, 27.5950, ST_GeomFromText('POINT(27.5950 47.1700)', 4326), 400, 85, 'community', 'open', '0232-255-190', 'North area, easy to access'),
('Liceul Mihail Sadoveanu', 'Strada Mihail Sadoveanu 12, Iasi', 47.1580, 27.5650, ST_GeomFromText('POINT(27.5650 47.1580)', 4326), 450, 90, 'school', 'open', '0232-230-891', 'West area, near Copou Park'),
('Universitatea Alexandru Ioan Cuza', 'Bulevardul Carol I 11, Iasi', 47.1756, 27.5768, ST_GeomFromText('POINT(27.5768 47.1756)', 4326), 800, 0, 'school', 'open', '0232-201-201', 'University campus shelter, main building');

INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude, status, started_at) VALUES
('flood', 'Bahlui Flood', 'The waters of the Bahlui River have exceeded flood levels in the Nicolina area. Multiple streets are flooded. Water levels reach 0.6m in some areas. Residents are advised to avoid the area.', 'high', 47.1500, 27.5950, 'active', NOW() - INTERVAL 3 HOUR),
('fire', 'Nicolina Block Fire', 'Fire broke out on the 5th floor of a building on Nicolina Street. Firefighters are responding with 5 vehicles. Area evacuated within a 150m radius.', 'extreme', 47.1440, 27.6000, 'active', NOW() - INTERVAL 1 HOUR),
('storm', 'Severe Copou Storm', 'Wind gusts exceeding 85 km/h have downed multiple trees in the Copou area. Power lines affected. Response teams on the ground.', 'moderate', 47.1780, 27.5650, 'active', NOW() - INTERVAL 30 MINUTE),
('earthquake', 'Earthquake felt in city center', 'Magnitude 4.0 earthquake on the Richter scale felt in the central area of Iasi. Structures have been inspected, no major damage reported.', 'moderate', 47.1620, 27.5830, 'active', NOW() - INTERVAL 6 HOUR),
('other', 'Gas leak in Tatarasi', 'Gas leak reported on Traian Vuia Street, Tatarasi neighborhood. Area isolated, gas response team dispatched. Residents evacuated as a precaution.', 'low', 47.1710, 27.5800, 'active', NOW() - INTERVAL 2 HOUR),
('earthquake', 'Earthquake near Holboca', 'Magnitude 5.2 earthquake detected near Holboca, approximately 20km northeast of Iasi city center. Tremors felt across the entire metropolitan area. Buildings inspected for structural damage.', 'extreme', 47.3500, 27.6500, 'active', NOW() - INTERVAL 15 MINUTE);

INSERT INTO evacuation_routes (name, shelter_id, from_latitude, from_longitude, from_geom, route_geometry, distance_meters, estimated_minutes, status, notes) VALUES
('Bahlui -> Sala Polivalenta Route', 1, 47.1500, 27.5950, ST_GeomFromText('POINT(27.5950 47.1500)', 4326), ST_GeomFromText('LINESTRING(27.5950 47.1500, 27.5960 47.1515, 27.5970 47.1530, 27.5980 47.1550)', 4326), 1200, 5, 'active', 'Main route from the flooded area'),
('Nicolina -> Student Complex Route', 6, 47.1460, 27.6020, ST_GeomFromText('POINT(27.6020 47.1460)', 4326), ST_GeomFromText('LINESTRING(27.6020 47.1460, 27.6060 47.1480, 27.6100 47.1510, 27.6120 47.1530)', 4326), 1800, 7, 'blocked', 'Route blocked due to the fire'),
('Copou -> CFR Stadium Route', 2, 47.1780, 27.5680, ST_GeomFromText('POINT(27.5680 47.1780)', 4326), ST_GeomFromText('LINESTRING(27.5680 47.1780, 27.5700 47.1740, 27.5720 47.1700, 27.5750 47.1630)', 4326), 2000, 8, 'active', 'Secondary route through the Copou area'),
('City Center -> Military Circle Route', 4, 47.1610, 27.5880, ST_GeomFromText('POINT(27.5880 47.1610)', 4326), ST_GeomFromText('LINESTRING(27.5880 47.1610, 27.5850 47.1605, 27.5820 47.1600)', 4326), 550, 2, 'active', 'Short, in the city center'),
('Tatarasi -> Community Center Route', 7, 47.1710, 27.5800, ST_GeomFromText('POINT(27.5800 47.1710)', 4326), ST_GeomFromText('LINESTRING(27.5800 47.1710, 27.5830 47.1705, 27.5880 47.1700, 27.5920 47.1700, 27.5950 47.1700)', 4326), 1600, 6, 'active', 'Accessible from the gas leak area');
