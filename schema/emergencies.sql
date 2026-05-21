-- ============================================================
-- Emergency Management Schema
-- Tables: shelters, evacuation_routes
-- Assumes Developer 1 owns: emergency_events, emergency_alerts
-- ============================================================

-- ------------------------------------------------------------
-- Shelters: designated safe locations for civilian evacuation
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shelters (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    name            VARCHAR(255)        NOT NULL,
    address         VARCHAR(500)        NOT NULL,
    latitude        DECIMAL(10, 7)      NOT NULL,
    longitude       DECIMAL(10, 7)      NOT NULL,
    geom_point      POINT               NOT NULL,
    capacity        INT UNSIGNED        NOT NULL DEFAULT 0,
    current_occupancy INT UNSIGNED      NOT NULL DEFAULT 0,
    status          ENUM('open', 'full', 'closed') NOT NULL DEFAULT 'open',
    contact_phone   VARCHAR(30)         DEFAULT NULL,
    notes           TEXT                DEFAULT NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    SPATIAL INDEX idx_shelters_geom (geom_point),
    INDEX idx_shelters_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Evacuation Routes: pre-defined paths from zones to shelters
-- ------------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


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

CREATE TABLE IF NOT EXISTS auth (
    user VARCHAR(32),
    pass VARCHAR(32)
)
