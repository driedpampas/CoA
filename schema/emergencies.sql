SET FOREIGN_KEY_CHECKS = 0; 

-- DROP TABLE IF EXISTS notifications; -- left commented to avoid tablespace/import conflicts; manage tablespaces manually if needed
DROP TABLE IF EXISTS shelters;
DROP TABLE IF EXISTS evacuation_routes;
DROP TABLE IF EXISTS emergency_events;
DROP TABLE IF EXISTS auth;

SET FOREIGN_KEY_CHECKS = 1; 

-- Shelters: designated safe locations for civilian evacuation
CREATE TABLE IF NOT EXISTS shelters (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    name            VARCHAR(255)        NOT NULL,
    address         VARCHAR(500)        NOT NULL,
    latitude        DECIMAL(10, 7)      NOT NULL,
    longitude       DECIMAL(10, 7)      NOT NULL,
    geom_point      POINT               NOT NULL,
    capacity        INT UNSIGNED        NOT NULL DEFAULT 0,
    current_occupancy INT UNSIGNED      NOT NULL DEFAULT 0,
    shelter_type    ENUM('stadium', 'school', 'military', 'community', 'other') NOT NULL DEFAULT 'community',
    status          ENUM('open', 'full', 'closed') NOT NULL DEFAULT 'open',
    contact_phone   VARCHAR(30)         DEFAULT NULL,
    notes           TEXT                DEFAULT NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    SPATIAL INDEX idx_shelters_geom (geom_point),
    INDEX idx_shelters_status (status)
);

-- Evacuation Routes: pre-defined paths from zones to shelters
CREATE TABLE IF NOT EXISTS evacuation_routes (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    name            VARCHAR(255)        NOT NULL,
    shelter_id      INT UNSIGNED        NOT NULL,
    from_latitude   DECIMAL(10, 7)      NOT NULL,
    from_longitude  DECIMAL(10, 7)      NOT NULL,
    from_geom       POINT               NOT NULL,
    route_geometry  LINESTRING          NOT NULL,
    distance_meters DOUBLE              DEFAULT NULL,
    estimated_minutes INT UNSIGNED      DEFAULT NULL,
    status          ENUM('active', 'blocked', 'closed') NOT NULL DEFAULT 'active',
    notes           TEXT                DEFAULT NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_evac_shelter (shelter_id),
    INDEX idx_evac_status (status),
    SPATIAL INDEX idx_evac_from_geom (from_geom),
    SPATIAL INDEX idx_evac_route_geom (route_geometry),
    CONSTRAINT fk_evac_shelter FOREIGN KEY (shelter_id) REFERENCES shelters(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS emergency_events (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type      ENUM('earthquake', 'flood', 'fire', 'storm', 'other') NOT NULL,
    title           VARCHAR(255) NOT NULL,
    description     TEXT,
    severity        ENUM('low', 'moderate', 'high', 'extreme') NOT NULL DEFAULT 'moderate',
    latitude        DECIMAL(10, 7),
    longitude       DECIMAL(10, 7),
    status          ENUM('active', 'resolved') NOT NULL DEFAULT 'active',
    started_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at     TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (id));
ALTER TABLE emergency_events ADD UNIQUE KEY unique_earthquake (event_type, started_at, latitude, longitude);

CREATE TABLE IF NOT EXISTS auth (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    user            VARCHAR(32)         NOT NULL,
    pass            VARCHAR(255)        NOT NULL,
    email           VARCHAR(255)        NOT NULL,
    bio             TEXT                DEFAULT NULL,
    notification_radius_km INT UNSIGNED NOT NULL DEFAULT 25,
    preferred_shelter_id INT UNSIGNED   DEFAULT NULL,
    role            ENUM('user', 'admin') DEFAULT 'user',
    email_verified  TINYINT(1)          NOT NULL DEFAULT 0,
    verification_token VARCHAR(64)      DEFAULT NULL,
    verification_expires DATETIME       DEFAULT NULL,
    reset_token     VARCHAR(64)         DEFAULT NULL,
    reset_expires   DATETIME            DEFAULT NULL,
    last_latitude   DECIMAL(10, 7)      DEFAULT NULL,
    last_longitude  DECIMAL(10, 7)      DEFAULT NULL,
    last_location_updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (user),
    UNIQUE KEY idx_auth_id (id),
    INDEX idx_verification_token (verification_token),
    INDEX idx_reset_token (reset_token),
    INDEX idx_auth_preferred_shelter (preferred_shelter_id),
    CONSTRAINT fk_preferred_shelter FOREIGN KEY (preferred_shelter_id) REFERENCES shelters(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED        DEFAULT NULL,
    title           VARCHAR(255)        NOT NULL,
    message         TEXT                NOT NULL,
    type            ENUM('event', 'shelter', 'system') NOT NULL DEFAULT 'system',
    severity        ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'info',
    reference_id    INT UNSIGNED        DEFAULT NULL,
    is_read         TINYINT(1)          NOT NULL DEFAULT 0,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read),
    INDEX idx_notifications_created (created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES auth(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);
