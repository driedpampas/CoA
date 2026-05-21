-- ============================================================
-- Sample data for COA Emergency Management System
-- Location: Bucharest, Romania
-- ============================================================

INSERT INTO shelters (name, address, latitude, longitude, geom_point, capacity, current_occupancy, status, contact_phone, notes) VALUES
('Sala Polivalenta', 'Strada Olimpilor 5-7, Bucuresti', 44.4218, 26.0850, ST_GeomFromText('POINT(26.0850 44.4218)', 4326), 5000, 1200, 'open', '021-314-2800', 'Capacitate mare, dotata cu generatoare'),
('Arena Nationala', 'Bulevardul Basarabia 37-39, Bucuresti', 44.4360, 26.1500, ST_GeomFromText('POINT(26.1500 44.4360)', 4326), 8000, 3500, 'open', '021-322-3456', 'Stadion principal, facilitati medicale disponibile'),
('Liceul Jean Monnet', 'Strada Jean Monnet 6, Bucuresti', 44.4520, 26.0880, ST_GeomFromText('POINT(26.0880 44.4520)', 4326), 800, 320, 'open', '021-631-0150', 'Adapost de sector, apa si alimente disponibile'),
('Centrul Militari', 'Strada Militari 92, Bucuresti', 44.4280, 26.0420, ST_GeomFromText('POINT(26.0420 44.4280)', 4326), 1200, 680, 'full', '021-435-7890', 'Plin la capacitate maxima'),
('Scoala Gimnaziala 79', 'Strada Drumul Sarii 120, Bucuresti', 44.4100, 26.0730, ST_GeomFromText('POINT(26.0730 44.4100)', 4326), 600, 150, 'open', '021-336-5678', 'Adapost de cartier, asistenta medicala'),
('Complexul Sportiv Dinamo', 'Strada Stefan cel Mare 7, Bucuresti', 44.4430, 26.1060, ST_GeomFromText('POINT(26.1060 44.4430)', 4326), 3000, 890, 'open', '021-201-4500', 'Baza sportiva, spatii multiple de cazare'),
('Cercul Militar National', 'Strada Mihai Eminescu 38, Bucuresti', 44.4410, 26.0920, ST_GeomFromText('POINT(26.0920 44.4410)', 4326), 400, 0, 'open', '021-313-6050', 'Centrul orasului, usor de accesat'),
('Liceul Gheorghe Lazar', 'Strada Vlaicu Voda 33, Bucuresti', 44.4180, 26.0780, ST_GeomFromText('POINT(26.0780 44.4180)', 4326), 700, 210, 'open', '021-330-8912', 'Zona sud, aproape de metrou');

INSERT INTO emergency_events (event_type, title, description, severity, latitude, longitude, status, started_at) VALUES
('flood', 'Inundatie Vacaresti', 'Apele s-au ridicat in zona Vacaresti, mai multe strazi sunt inundate. Nivelul apei atinge 0.5m in unele zone. Locuitii sunt sfatuiti sa evite zona.', 'high', 44.3980, 26.1130, 'active', NOW() - INTERVAL 3 HOUR),
('fire', 'Incendiu bloc Militari', 'Foc izbucnit la etajul 8 al unui bloc de pe strada Militari. Pompierii intervin cu 6 autospeciale. Zona evacuata pe o raza de 200m.', 'extreme', 44.4310, 26.0510, 'active', NOW() - INTERVAL 1 HOUR),
('storm', 'Furtuna puternica Sector 3', 'Rafale de vant de peste 90 km/h au doborat mai multi copaci in Sectorul 3. Linii de electricitate afectate. Echipe de interventie pe teren.', 'moderate', 44.4350, 26.1300, 'active', NOW() - INTERVAL 30 MINUTE),
('earthquake', 'Seism simtit in centru', 'Seism cu magnitudinea 4.2 pe scara Richter simtit in zona centrala a Bucurestiului. Structurile au fost verificate, nu sunt daune majore raportate.', 'moderate', 44.4400, 26.0950, 'active', NOW() - INTERVAL 6 HOUR),
('other', 'Scurgere gaze Sector 1', 'Pierdere de gaze raportata pe strada Barbu Vaida, Sector 1. Zona izolata, echipa de interventie gaze trimisa. Locuitii evacuati preventiv.', 'low', 44.4530, 26.0750, 'active', NOW() - INTERVAL 2 HOUR);

INSERT INTO evacuation_routes (name, shelter_id, from_latitude, from_longitude, from_geom, route_geometry, distance_meters, estimated_minutes, status, notes) VALUES
('Ruta Vacaresti -> Arena Nationala', 2, 44.3980, 26.1130, ST_GeomFromText('POINT(26.1130 44.3980)', 4326), ST_GeomFromText('LINESTRING(26.1130 44.3980, 26.1200 44.4050, 26.1300 44.4150, 26.1400 44.4260, 26.1500 44.4360)', 4326), 5200, 15, 'active', 'Ruta principala dinspre zona inundata'),
('Ruta Militari -> Centrul Militari', 4, 44.4290, 26.0490, ST_GeomFromText('POINT(26.0490 44.4290)', 4326), ST_GeomFromText('LINESTRING(26.0490 44.4290, 26.0450 44.4285, 26.0420 44.4280)', 4326), 800, 3, 'blocked', 'Ruta blocata din cauza incendiului'),
('Ruta Sector 3 -> Dinamo', 6, 44.4320, 26.1250, ST_GeomFromText('POINT(26.1250 44.4320)', 4326), ST_GeomFromText('LINESTRING(26.1250 44.4320, 26.1180 44.4360, 26.1100 44.4400, 26.1060 44.4430)', 4326), 2100, 8, 'active', 'Ruta secundara prin Sector 3'),
('Ruta Centru -> Cercul Militar', 7, 44.4410, 26.1000, ST_GeomFromText('POINT(26.1000 44.4410)', 4326), ST_GeomFromText('LINESTRING(26.1000 44.4410, 26.0960 44.4410, 26.0920 44.4410)', 4326), 650, 2, 'active', 'Scurta, in centrul orasului'),
('Ruta Sector 1 -> Liceul Jean Monnet', 3, 44.4510, 26.0800, ST_GeomFromText('POINT(26.0800 44.4510)', 4326), ST_GeomFromText('LINESTRING(26.0800 44.4510, 26.0830 44.4515, 26.0860 44.4518, 26.0880 44.4520)', 4326), 750, 3, 'active', 'Accesibil pentru zona de scurgere gaze');
