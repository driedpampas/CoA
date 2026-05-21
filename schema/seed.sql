-- ============================================================
-- Sample data for COA Emergency Management System
-- Location: Iași, Romania
-- ============================================================

INSERT INTO shelters (name, address, latitude, longitude, geom_point, capacity, current_occupancy, shelter_type, status, contact_phone, notes) VALUES
('Sala Polivalenta', 'Strada Palat 5-7, Iasi', 47.1550, 27.5980, ST_GeomFromText('POINT(27.5980 47.1550)', 4326), 4000, 950, 'stadium', 'open', '0232-218-400', 'Capacitate mare, dotata cu generatoare'),
('Stadionul CFR', 'Strada Calea Chisinaului 35, Iasi', 47.1630, 27.5750, ST_GeomFromText('POINT(27.5750 47.1630)', 4326), 6000, 2800, 'stadium', 'open', '0232-210-780', 'Stadion principala, facilitati medicale disponibile'),
('Liceul Costache Negri', 'Strada Costache Negri 29, Iasi', 47.1750, 27.5780, ST_GeomFromText('POINT(27.5780 47.1750)', 4326), 600, 180, 'school', 'open', '0232-253-012', 'Adapost de cartier, apa si alimente disponibile'),
('Cercul Militar Iasi', 'Strada Stefan cel Mare 62, Iasi', 47.1600, 27.5820, ST_GeomFromText('POINT(27.5820 47.1600)', 4326), 350, 0, 'military', 'open', '0232-211-305', 'Centrul orasului, usor de accesat'),
('Scoala Ion Heliade Radulescu', 'Strada Vasile Lupu 85, Iasi', 47.1480, 27.5700, ST_GeomFromText('POINT(27.5700 47.1480)', 4326), 500, 120, 'school', 'open', '0232-236-578', 'Zona Nicolina, asistenta medicala'),
('Complexul Studentesc Tudor Vladimirescu', 'Bulevardul Tudor Vladimirescu 50, Iasi', 47.1530, 27.6120, ST_GeomFromText('POINT(27.6120 47.1530)', 4326), 1500, 720, 'community', 'full', '0232-242-450', 'Plin la capacitate maxima'),
('Centrul Comunitar Alexandru cel Bun', 'Strada Sarbatorilor 10, Iasi', 47.1700, 27.5950, ST_GeomFromText('POINT(27.5950 47.1700)', 4326), 400, 85, 'community', 'open', '0232-255-190', 'Zona nord, usor de accesat'),
('Liceul Mihail Sadoveanu', 'Strada Mihail Sadoveanu 12, Iasi', 47.1580, 27.5650, ST_GeomFromText('POINT(27.5650 47.1580)', 4326), 450, 90, 'school', 'open', '0232-230-891', 'Zona vest, aproape de Parcul Copou');

INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude, status, started_at) VALUES
('flood', 'Inundatie Bahlui', 'Apele raului Bahlui au depasit cotele de inundatie in zona Nicolina. Mai multe strazi sunt inundate. Nivelul apei atinge 0.6m in unele zone. Locuitii sunt sfatuiti sa evite zona.', 'high', 47.1500, 27.5950, 'active', NOW() - INTERVAL 3 HOUR),
('fire', 'Incendiu bloc Nicolina', 'Foc izbucnit la etajul 5 al unui bloc de pe strada Nicolina. Pompierii intervin cu 5 autospeciale. Zona evacuata pe o raza de 150m.', 'extreme', 47.1440, 27.6000, 'active', NOW() - INTERVAL 1 HOUR),
('storm', 'Furtuna puternica Copou', 'Rafale de vant de peste 85 km/h au doborat mai multi copaci in zona Copou. Linii de electricitate afectate. Echipe de interventie pe teren.', 'moderate', 47.1780, 27.5650, 'active', NOW() - INTERVAL 30 MINUTE),
('earthquake', 'Seism simtit in centru', 'Seism cu magnitudinea 4.0 pe scara Richter simtit in zona centrala a Iasului. Structurile au fost verificate, nu sunt daune majore raportate.', 'moderate', 47.1620, 27.5830, 'active', NOW() - INTERVAL 6 HOUR),
('other', 'Scurgere gaze Tatarasi', 'Pierdere de gaze raportata pe strada Traian Vuia, cartierul Tatarasi. Zona izolata, echipa de interventie gaze trimisa. Locuitii evacuati preventiv.', 'low', 47.1710, 27.5800, 'active', NOW() - INTERVAL 2 HOUR);

INSERT INTO evacuation_routes (name, shelter_id, from_latitude, from_longitude, from_geom, route_geometry, distance_meters, estimated_minutes, status, notes) VALUES
('Ruta Bahlui -> Sala Polivalenta', 1, 47.1500, 27.5950, ST_GeomFromText('POINT(27.5950 47.1500)', 4326), ST_GeomFromText('LINESTRING(27.5950 47.1500, 27.5960 47.1515, 27.5970 47.1530, 27.5980 47.1550)', 4326), 1200, 5, 'active', 'Ruta principala dinspre zona inundata'),
('Ruta Nicolina -> Complexul Studentesc', 6, 47.1460, 27.6020, ST_GeomFromText('POINT(27.6020 47.1460)', 4326), ST_GeomFromText('LINESTRING(27.6020 47.1460, 27.6060 47.1480, 27.6100 47.1510, 27.6120 47.1530)', 4326), 1800, 7, 'blocked', 'Ruta blocata din cauza incendiului'),
('Ruta Copou -> Stadionul CFR', 2, 47.1780, 27.5680, ST_GeomFromText('POINT(27.5680 47.1780)', 4326), ST_GeomFromText('LINESTRING(27.5680 47.1780, 27.5700 47.1740, 27.5720 47.1700, 27.5750 47.1630)', 4326), 2000, 8, 'active', 'Ruta secundara prin zona Copou'),
('Ruta Centru -> Cercul Militar', 4, 47.1610, 27.5880, ST_GeomFromText('POINT(27.5880 47.1610)', 4326), ST_GeomFromText('LINESTRING(27.5880 47.1610, 27.5850 47.1605, 27.5820 47.1600)', 4326), 550, 2, 'active', 'Scurta, in centrul orasului'),
('Ruta Tatarasi -> Centrul Comunitar', 7, 47.1710, 27.5800, ST_GeomFromText('POINT(27.5800 47.1710)', 4326), ST_GeomFromText('LINESTRING(27.5800 47.1710, 27.5830 47.1705, 27.5880 47.1700, 27.5920 47.1700, 27.5950 47.1700)', 4326), 1600, 6, 'active', 'Accesibil pentru zona de scurgere gaze');
