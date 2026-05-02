CREATE TABLE session_statuses (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(60) NOT NULL,
    sort_order INT         NOT NULL DEFAULT 0,
    CONSTRAINT uq_session_statuses_name UNIQUE (name)
);

CREATE TABLE absence_types (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    CONSTRAINT uq_absence_types_name UNIQUE (name)
);

CREATE TABLE payment_methods (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    CONSTRAINT uq_payment_methods_name UNIQUE (name)
);

CREATE TABLE payment_statuses (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    CONSTRAINT uq_payment_statuses_name UNIQUE (name)
);

CREATE TABLE sale_statuses (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    CONSTRAINT uq_sale_statuses_name UNIQUE (name)
);

CREATE TABLE stock_movement_types (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    CONSTRAINT uq_stock_movement_types_name UNIQUE (name)
);

CREATE TABLE skin_types (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    CONSTRAINT uq_skin_types_name UNIQUE (name)
);

CREATE TABLE variations (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    CONSTRAINT uq_variations_name UNIQUE (name)
);

CREATE TABLE plans (
    id                     SERIAL PRIMARY KEY,
    code                   VARCHAR(30) NOT NULL,
    name                   VARCHAR(60) NOT NULL,
    description            TEXT,
    max_workers            INT         NOT NULL DEFAULT 3,
    allows_online_clients  BOOLEAN     NOT NULL DEFAULT FALSE,
    allows_emails          BOOLEAN     NOT NULL DEFAULT FALSE,
    allows_public_page     BOOLEAN     NOT NULL DEFAULT FALSE,
    allows_custom_domain   BOOLEAN     NOT NULL DEFAULT FALSE,
    is_active              BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at             TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at             TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_plans_code         UNIQUE (code),
    CONSTRAINT chk_plans_max_workers CHECK (max_workers > 0)
);

CREATE TABLE centers (
    id                 SERIAL PRIMARY KEY,
    uuid               UUID         NOT NULL,
    name               VARCHAR(120) NOT NULL,
    slug               VARCHAR(80)  NOT NULL,
    custom_domain      VARCHAR(255),
    is_domain_verified BOOLEAN      NOT NULL DEFAULT FALSE,
    plan_id            INT          NOT NULL,
    is_active          BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at         TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at         TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uq_centers_uuid          UNIQUE (uuid),
    CONSTRAINT uq_centers_slug          UNIQUE (slug),
    CONSTRAINT uq_centers_custom_domain UNIQUE (custom_domain),
    CONSTRAINT fk_centers_plan          FOREIGN KEY (plan_id)
        REFERENCES plans (id) ON DELETE RESTRICT
);

CREATE TABLE center_files (
    id         SERIAL PRIMARY KEY,
    center_id  INT          NOT NULL,
    type       VARCHAR(30)  NOT NULL,
    path       VARCHAR(255) NOT NULL,
    mime_type  VARCHAR(100),
    sort_order INT          NOT NULL DEFAULT 0,
    is_active  BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uq_center_files_id_center UNIQUE (id, center_id),
    CONSTRAINT fk_center_files_center    FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE INDEX idx_center_files_center_type ON center_files (center_id, type);

CREATE TABLE users (
    id                  SERIAL PRIMARY KEY,
    center_id           INT,
    name                VARCHAR(120) NOT NULL,
    email               VARCHAR(150),
    phone               VARCHAR(30),
    birth_date          DATE,
    password            VARCHAR(255),
    email_verified_at   TIMESTAMPTZ,
    remember_token      VARCHAR(100),
    registration_source VARCHAR(20),
    failed_attempts     INT         NOT NULL DEFAULT 0,
    locked_until        TIMESTAMPTZ,
    is_active           BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_users_center_email      UNIQUE (center_id, email),
    CONSTRAINT uq_users_id_center         UNIQUE (id, center_id),
    CONSTRAINT chk_users_registration_src CHECK (
        registration_source IS NULL
        OR registration_source IN ('online', 'staff')
    ),
    CONSTRAINT fk_users_center FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE time_slots (
    id         SERIAL PRIMARY KEY,
    center_id  INT         NOT NULL,
    name       VARCHAR(50),
    start_time TIME        NOT NULL,
    end_time   TIME        NOT NULL,
    is_active  BOOLEAN     NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_time_slots_id_center        UNIQUE (id, center_id),
    CONSTRAINT uq_time_slots_center_times     UNIQUE (center_id, start_time, end_time),
    CONSTRAINT chk_time_slots_end             CHECK (end_time > start_time),
    CONSTRAINT fk_time_slots_center           FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE worker_schedules (
    id           SERIAL PRIMARY KEY,
    center_id    INT  NOT NULL,
    worker_id    INT  NOT NULL,
    weekday      INT  NOT NULL,
    time_slot_id INT  NOT NULL,
    start_date   DATE NOT NULL,
    end_date     DATE,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_worker_schedules_key    UNIQUE (center_id, worker_id, weekday, time_slot_id, start_date),
    CONSTRAINT chk_worker_schedules_day   CHECK (weekday BETWEEN 1 AND 7),
    CONSTRAINT chk_worker_schedules_dates CHECK (end_date IS NULL OR end_date >= start_date),
    CONSTRAINT fk_worker_schedules_center FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_worker_schedules_worker FOREIGN KEY (worker_id, center_id)    REFERENCES users (id, center_id),
    CONSTRAINT fk_worker_schedules_slot   FOREIGN KEY (time_slot_id, center_id) REFERENCES time_slots (id, center_id)
);
CREATE INDEX idx_worker_schedules_center_day_slot ON worker_schedules (center_id, weekday, time_slot_id);

CREATE TABLE worker_absences (
    id              SERIAL PRIMARY KEY,
    center_id       INT         NOT NULL,
    worker_id       INT         NOT NULL,
    date            DATE        NOT NULL,
    start_time      TIME,
    end_time        TIME,
    is_full_day     BOOLEAN     NOT NULL DEFAULT FALSE,
    reason          VARCHAR(120),
    absence_type_id INT,
    notes           TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_worker_absences_times CHECK (end_time IS NULL OR end_time > start_time),
    CONSTRAINT fk_worker_absences_center FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_worker_absences_worker FOREIGN KEY (worker_id, center_id) REFERENCES users (id, center_id),
    CONSTRAINT fk_worker_absences_type   FOREIGN KEY (absence_type_id)
        REFERENCES absence_types (id) ON DELETE RESTRICT
);
CREATE INDEX idx_worker_absences_key ON worker_absences (center_id, worker_id, date, start_time, end_time);

CREATE TABLE worker_extra_availabilities (
    id         SERIAL PRIMARY KEY,
    center_id  INT         NOT NULL,
    worker_id  INT         NOT NULL,
    date       DATE        NOT NULL,
    start_time TIME        NOT NULL,
    end_time   TIME        NOT NULL,
    reason     VARCHAR(120),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_worker_extra_avail_key    UNIQUE (center_id, worker_id, date, start_time, end_time),
    CONSTRAINT chk_worker_extra_avail_times CHECK (end_time > start_time),
    CONSTRAINT fk_worker_extra_avail_center FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_worker_extra_avail_worker FOREIGN KEY (worker_id, center_id) REFERENCES users (id, center_id)
);

CREATE TABLE rooms (
    id            SERIAL PRIMARY KEY,
    center_id     INT          NOT NULL,
    name          VARCHAR(120) NOT NULL,
    grid_position JSONB,
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uq_rooms_id_center   UNIQUE (id, center_id),
    CONSTRAINT uq_rooms_center_name UNIQUE (center_id, name),
    CONSTRAINT fk_rooms_center      FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE treatments (
    id               SERIAL PRIMARY KEY,
    center_id        INT            NOT NULL,
    name             VARCHAR(120)   NOT NULL,
    duration_minutes INT            NOT NULL,
    price            DECIMAL(10, 2) NOT NULL,
    is_active        BOOLEAN        NOT NULL DEFAULT TRUE,
    created_at       TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_treatments_id_center    UNIQUE (id, center_id),
    CONSTRAINT uq_treatments_center_name  UNIQUE (center_id, name),
    CONSTRAINT chk_treatments_duration    CHECK (duration_minutes > 0),
    CONSTRAINT chk_treatments_price       CHECK (price >= 0),
    CONSTRAINT fk_treatments_center       FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE machines (
    id            SERIAL PRIMARY KEY,
    center_id     INT          NOT NULL,
    name          VARCHAR(120) NOT NULL,
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    is_mobile     BOOLEAN      NOT NULL DEFAULT FALSE,
    fixed_room_id INT,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uq_machines_id_center    UNIQUE (id, center_id),
    CONSTRAINT uq_machines_center_name  UNIQUE (center_id, name),
    CONSTRAINT chk_machines_mobile_room CHECK (NOT (is_mobile = TRUE AND fixed_room_id IS NOT NULL)),
    CONSTRAINT fk_machines_center       FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_machines_room         FOREIGN KEY (fixed_room_id)
        REFERENCES rooms (id) ON DELETE SET NULL
);

CREATE TABLE machine_treatment (
    center_id    INT NOT NULL,
    machine_id   INT NOT NULL,
    treatment_id INT NOT NULL,
    CONSTRAINT pk_machine_treatment         PRIMARY KEY (machine_id, treatment_id),
    CONSTRAINT fk_machine_treatment_center  FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_machine_treatment_machine FOREIGN KEY (machine_id, center_id)
        REFERENCES machines (id, center_id) ON DELETE CASCADE,
    CONSTRAINT fk_machine_treatment_treat   FOREIGN KEY (treatment_id, center_id)
        REFERENCES treatments (id, center_id) ON DELETE CASCADE
);
CREATE INDEX idx_machine_treatment_center_machine ON machine_treatment (center_id, machine_id);
CREATE INDEX idx_machine_treatment_center_treat   ON machine_treatment (center_id, treatment_id);

CREATE TABLE client_profiles (
    id                 SERIAL PRIMARY KEY,
    center_id          INT         NOT NULL,
    user_id            INT         NOT NULL,
    skin_type_id       INT         NOT NULL,
    last_review_date   DATE        NOT NULL DEFAULT CURRENT_DATE,
    updated_by_user_id INT         NOT NULL,
    general_notes      TEXT,
    is_active          BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_client_profiles_id_center UNIQUE (id, center_id),
    CONSTRAINT fk_client_profiles_center    FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_client_profiles_user      FOREIGN KEY (user_id, center_id)           REFERENCES users (id, center_id),
    CONSTRAINT fk_client_profiles_skin_type FOREIGN KEY (skin_type_id)
        REFERENCES skin_types (id) ON DELETE RESTRICT,
    CONSTRAINT fk_client_profiles_updater   FOREIGN KEY (updated_by_user_id, center_id) REFERENCES users (id, center_id)
);
CREATE INDEX idx_client_profiles_center_user ON client_profiles (center_id, user_id);
CREATE INDEX idx_client_profiles_skin_type   ON client_profiles (skin_type_id);

CREATE TABLE skin_evaluations (
    id                SERIAL PRIMARY KEY,
    center_id         INT         NOT NULL,
    user_id           INT         NOT NULL,
    client_profile_id INT         NOT NULL,
    skin_type_id      INT         NOT NULL,
    evaluation_date   DATE        NOT NULL DEFAULT CURRENT_DATE,
    professional_id   INT         NOT NULL,
    general_notes     TEXT,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_skin_evaluations_id_center UNIQUE (id, center_id),
    CONSTRAINT fk_skin_eval_center           FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_skin_eval_user             FOREIGN KEY (user_id, center_id)           REFERENCES users (id, center_id),
    CONSTRAINT fk_skin_eval_profile          FOREIGN KEY (client_profile_id, center_id) REFERENCES client_profiles (id, center_id),
    CONSTRAINT fk_skin_eval_skin_type        FOREIGN KEY (skin_type_id)
        REFERENCES skin_types (id) ON DELETE RESTRICT,
    CONSTRAINT fk_skin_eval_professional     FOREIGN KEY (professional_id, center_id)   REFERENCES users (id, center_id)
);
CREATE INDEX idx_skin_eval_center_user_date    ON skin_evaluations (center_id, user_id, evaluation_date);
CREATE INDEX idx_skin_eval_center_profile_date ON skin_evaluations (center_id, client_profile_id, evaluation_date);

CREATE TABLE skin_evaluation_variation (
    skin_evaluation_id INT         NOT NULL,
    variation_id       INT         NOT NULL,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT pk_skin_evaluation_variation  PRIMARY KEY (skin_evaluation_id, variation_id),
    CONSTRAINT fk_skin_eval_var_eval         FOREIGN KEY (skin_evaluation_id) REFERENCES skin_evaluations (id) ON DELETE CASCADE,
    CONSTRAINT fk_skin_eval_var_variation    FOREIGN KEY (variation_id)       REFERENCES variations (id) ON DELETE CASCADE
);

CREATE TABLE client_profile_variation (
    client_profile_id INT NOT NULL,
    variation_id      INT NOT NULL,
    CONSTRAINT pk_client_profile_variation   PRIMARY KEY (client_profile_id, variation_id),
    CONSTRAINT fk_client_prof_var_profile    FOREIGN KEY (client_profile_id) REFERENCES client_profiles (id) ON DELETE CASCADE,
    CONSTRAINT fk_client_prof_var_variation  FOREIGN KEY (variation_id)      REFERENCES variations (id) ON DELETE CASCADE
);

CREATE TABLE treatment_suitability_evaluations (
    id                   SERIAL PRIMARY KEY,
    center_id            INT         NOT NULL,
    user_id              INT         NOT NULL,
    treatment_id         INT         NOT NULL,
    reviewed_by_user_id  INT         NOT NULL,
    review_date          DATE        NOT NULL DEFAULT CURRENT_DATE,
    is_suitable          BOOLEAN     NOT NULL DEFAULT TRUE,
    unsuitability_reason VARCHAR(150),
    notes                TEXT,
    is_active            BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at           TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at           TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_treat_suit_eval_id_center UNIQUE (id, center_id),
    CONSTRAINT fk_treat_suit_eval_center    FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_treat_suit_eval_user      FOREIGN KEY (user_id, center_id)           REFERENCES users (id, center_id),
    CONSTRAINT fk_treat_suit_eval_treatment FOREIGN KEY (treatment_id, center_id)      REFERENCES treatments (id, center_id),
    CONSTRAINT fk_treat_suit_eval_reviewer  FOREIGN KEY (reviewed_by_user_id, center_id) REFERENCES users (id, center_id)
);
CREATE INDEX idx_treat_suit_eval_center_user      ON treatment_suitability_evaluations (center_id, user_id);
CREATE INDEX idx_treat_suit_eval_center_treatment ON treatment_suitability_evaluations (center_id, treatment_id);
CREATE INDEX idx_treat_suit_eval_key              ON treatment_suitability_evaluations (center_id, user_id, treatment_id, review_date);

CREATE TABLE user_files (
    id                 SERIAL PRIMARY KEY,
    center_id          INT          NOT NULL,
    user_id            INT          NOT NULL,
    skin_evaluation_id INT,
    type               VARCHAR(30)  NOT NULL,
    category           VARCHAR(40)  NOT NULL,
    path               VARCHAR(255) NOT NULL,
    mime_type          VARCHAR(100),
    notes              TEXT,
    created_at         TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uq_user_files_id_center       UNIQUE (id, center_id),
    CONSTRAINT fk_user_files_center          FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_files_user            FOREIGN KEY (user_id, center_id)            REFERENCES users (id, center_id),
    CONSTRAINT fk_user_files_skin_evaluation FOREIGN KEY (skin_evaluation_id, center_id) REFERENCES skin_evaluations (id, center_id)
);
CREATE INDEX idx_user_files_center_user      ON user_files (center_id, user_id);
CREATE INDEX idx_user_files_center_skin_eval ON user_files (center_id, skin_evaluation_id);

CREATE TABLE appointments (
    id                      SERIAL PRIMARY KEY,
    center_id               INT            NOT NULL,
    treatment_id            INT            NOT NULL,
    room_id                 INT            NOT NULL,
    client_id               INT            NOT NULL,
    worker_id               INT            NOT NULL,
    machine_id              INT,
    starts_at               TIMESTAMPTZ    NOT NULL,
    ends_at                 TIMESTAMPTZ    NOT NULL,
    actual_duration_minutes INT,
    booking_source          VARCHAR(50)    NOT NULL,
    status_id               INT            NOT NULL,
    reserved_price          DECIMAL(10, 2),
    cancelled_at            TIMESTAMPTZ,
    notes                   TEXT,
    created_at              TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at              TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_appointments_id_center  UNIQUE (id, center_id),
    CONSTRAINT chk_appointments_times     CHECK (ends_at > starts_at),
    CONSTRAINT fk_appointments_center     FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_status     FOREIGN KEY (status_id)
        REFERENCES session_statuses (id) ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_client     FOREIGN KEY (client_id, center_id)     REFERENCES users (id, center_id),
    CONSTRAINT fk_appointments_worker     FOREIGN KEY (worker_id, center_id)     REFERENCES users (id, center_id),
    CONSTRAINT fk_appointments_treatment  FOREIGN KEY (treatment_id, center_id)  REFERENCES treatments (id, center_id),
    CONSTRAINT fk_appointments_room       FOREIGN KEY (room_id, center_id)       REFERENCES rooms (id, center_id),
    CONSTRAINT fk_appointments_machine    FOREIGN KEY (machine_id)
        REFERENCES machines (id) ON DELETE SET NULL
);
CREATE INDEX idx_appointments_center_starts    ON appointments (center_id, starts_at);
CREATE INDEX idx_appointments_center_worker    ON appointments (center_id, worker_id, starts_at);
CREATE INDEX idx_appointments_center_room      ON appointments (center_id, room_id, starts_at);
CREATE INDEX idx_appointments_center_client    ON appointments (center_id, client_id, starts_at);
CREATE INDEX idx_appointments_center_treatment ON appointments (center_id, treatment_id, starts_at);

CREATE TABLE appointment_assistants (
    appointment_id INT  NOT NULL,
    center_id      INT  NOT NULL,
    user_id        INT  NOT NULL,
    notes          TEXT,
    CONSTRAINT pk_appointment_assistants      PRIMARY KEY (appointment_id, user_id),
    CONSTRAINT fk_appt_assistants_center      FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_appt_assistants_appointment FOREIGN KEY (appointment_id, center_id)
        REFERENCES appointments (id, center_id) ON DELETE CASCADE,
    CONSTRAINT fk_appt_assistants_user        FOREIGN KEY (user_id, center_id)
        REFERENCES users (id, center_id) ON DELETE CASCADE
);
CREATE INDEX idx_appointment_assistants_center_user ON appointment_assistants (center_id, user_id);

CREATE TABLE sales (
    id                 SERIAL PRIMARY KEY,
    center_id          INT            NOT NULL,
    client_id          INT            NOT NULL,
    appointment_id     INT,
    created_by_user_id INT            NOT NULL,
    subtotal           DECIMAL(10, 2) NOT NULL,
    discount           DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total              DECIMAL(10, 2) NOT NULL,
    status_id          INT            NOT NULL,
    notes              TEXT,
    created_at         TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at         TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_sales_id_center      UNIQUE (id, center_id),
    CONSTRAINT fk_sales_center         FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sales_client         FOREIGN KEY (client_id, center_id)        REFERENCES users (id, center_id),
    CONSTRAINT fk_sales_appointment    FOREIGN KEY (appointment_id)
        REFERENCES appointments (id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_creator        FOREIGN KEY (created_by_user_id, center_id) REFERENCES users (id, center_id),
    CONSTRAINT fk_sales_status         FOREIGN KEY (status_id)
        REFERENCES sale_statuses (id) ON DELETE RESTRICT
);
CREATE INDEX idx_sales_center_client      ON sales (center_id, client_id);
CREATE INDEX idx_sales_center_appointment ON sales (center_id, appointment_id);
CREATE INDEX idx_sales_center_status      ON sales (center_id, status_id);

CREATE TABLE sale_lines (
    id            SERIAL PRIMARY KEY,
    sale_id       INT            NOT NULL,
    center_id     INT            NOT NULL,
    type          VARCHAR(20)    NOT NULL,
    reference_id  INT            NOT NULL,
    description   VARCHAR(200)   NOT NULL,
    quantity      DECIMAL(8, 3)  NOT NULL DEFAULT 1,
    unit_price    DECIMAL(10, 2) NOT NULL,
    line_discount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    line_total    DECIMAL(10, 2) NOT NULL,
    CONSTRAINT chk_sale_lines_type CHECK (type IN ('treatment', 'product')),
    CONSTRAINT fk_sale_lines_sale  FOREIGN KEY (sale_id, center_id) REFERENCES sales (id, center_id)
);
CREATE INDEX idx_sale_lines_center_sale ON sale_lines (center_id, sale_id);

CREATE TABLE payments (
    id                       SERIAL PRIMARY KEY,
    center_id                INT            NOT NULL,
    sale_id                  INT            NOT NULL,
    payment_method_id        INT            NOT NULL,
    status_id                INT            NOT NULL,
    amount                   DECIMAL(10, 2) NOT NULL,
    currency                 CHAR(3)        NOT NULL DEFAULT 'EUR',
    stripe_payment_intent_id VARCHAR(100),
    stripe_charge_id         VARCHAR(100),
    stripe_metadata          JSONB,
    processed_at             TIMESTAMPTZ,
    created_at               TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at               TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_payments_id_center     UNIQUE (id, center_id),
    CONSTRAINT uq_payments_stripe_intent UNIQUE (stripe_payment_intent_id),
    CONSTRAINT fk_payments_sale          FOREIGN KEY (sale_id, center_id)  REFERENCES sales (id, center_id),
    CONSTRAINT fk_payments_method        FOREIGN KEY (payment_method_id)
        REFERENCES payment_methods (id) ON DELETE RESTRICT,
    CONSTRAINT fk_payments_status        FOREIGN KEY (status_id)
        REFERENCES payment_statuses (id) ON DELETE RESTRICT
);
CREATE INDEX idx_payments_center_sale ON payments (center_id, sale_id);

CREATE TABLE invoices (
    id                SERIAL PRIMARY KEY,
    center_id         INT            NOT NULL,
    sale_id           INT            NOT NULL,
    client_id         INT            NOT NULL,
    invoice_number    VARCHAR(20)    NOT NULL,
    issued_date       DATE           NOT NULL DEFAULT CURRENT_DATE,
    subtotal          DECIMAL(10, 2) NOT NULL,
    vat_percentage    DECIMAL(5, 2)  NOT NULL DEFAULT 21,
    vat_amount        DECIMAL(10, 2) NOT NULL,
    total             DECIMAL(10, 2) NOT NULL,
    client_snapshot   JSONB          NOT NULL,
    center_snapshot   JSONB          NOT NULL,
    pdf_path          VARCHAR(255),
    issued_by_user_id INT            NOT NULL,
    created_at        TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_invoices_sale_id         UNIQUE (sale_id),
    CONSTRAINT uq_invoices_id_center       UNIQUE (id, center_id),
    CONSTRAINT uq_invoices_center_number   UNIQUE (center_id, invoice_number),
    CONSTRAINT fk_invoices_center          FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_sale            FOREIGN KEY (sale_id, center_id)           REFERENCES sales (id, center_id),
    CONSTRAINT fk_invoices_client          FOREIGN KEY (client_id, center_id)         REFERENCES users (id, center_id),
    CONSTRAINT fk_invoices_issuer          FOREIGN KEY (issued_by_user_id, center_id) REFERENCES users (id, center_id)
);
CREATE INDEX idx_invoices_center_client ON invoices (center_id, client_id);
CREATE INDEX idx_invoices_center_date   ON invoices (center_id, issued_date);

CREATE TABLE products (
    id               SERIAL PRIMARY KEY,
    center_id        INT            NOT NULL,
    name             VARCHAR(120)   NOT NULL,
    description      TEXT,
    measurement_unit VARCHAR(20)    NOT NULL DEFAULT 'unit',
    sale_price       DECIMAL(10, 2),
    cost_price       DECIMAL(10, 2),
    minimum_stock    DECIMAL(10, 3) NOT NULL DEFAULT 0,
    is_sellable      BOOLEAN        NOT NULL DEFAULT TRUE,
    is_active        BOOLEAN        NOT NULL DEFAULT TRUE,
    created_at       TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_products_id_center    UNIQUE (id, center_id),
    CONSTRAINT uq_products_center_name  UNIQUE (center_id, name),
    CONSTRAINT fk_products_center       FOREIGN KEY (center_id)
        REFERENCES centers (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE product_stocks (
    id               SERIAL PRIMARY KEY,
    center_id        INT            NOT NULL,
    product_id       INT            NOT NULL,
    current_quantity DECIMAL(10, 3) NOT NULL DEFAULT 0,
    created_at       TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_product_stocks_id_center      UNIQUE (id, center_id),
    CONSTRAINT uq_product_stocks_center_product UNIQUE (center_id, product_id),
    CONSTRAINT fk_product_stocks_product        FOREIGN KEY (product_id, center_id) REFERENCES products (id, center_id)
);

CREATE TABLE stock_movements (
    id                SERIAL PRIMARY KEY,
    center_id         INT            NOT NULL,
    product_id        INT            NOT NULL,
    movement_type_id  INT            NOT NULL,
    quantity          DECIMAL(10, 3) NOT NULL,
    previous_quantity DECIMAL(10, 3) NOT NULL,
    new_quantity      DECIMAL(10, 3) NOT NULL,
    reference_type    VARCHAR(30),
    reference_id      INT,
    user_id           INT            NOT NULL,
    reason            VARCHAR(200),
    created_at        TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_stock_movements_id_center UNIQUE (id, center_id),
    CONSTRAINT fk_stock_movements_product   FOREIGN KEY (product_id, center_id) REFERENCES products (id, center_id),
    CONSTRAINT fk_stock_movements_type      FOREIGN KEY (movement_type_id)
        REFERENCES stock_movement_types (id) ON DELETE RESTRICT,
    CONSTRAINT fk_stock_movements_user      FOREIGN KEY (user_id, center_id)     REFERENCES users (id, center_id)
);
CREATE INDEX idx_stock_movements_center_product   ON stock_movements (center_id, product_id);
CREATE INDEX idx_stock_movements_center_reference ON stock_movements (center_id, reference_type, reference_id);

CREATE TABLE appointment_products (
    id             SERIAL PRIMARY KEY,
    appointment_id INT            NOT NULL,
    center_id      INT            NOT NULL,
    product_id     INT            NOT NULL,
    quantity       DECIMAL(10, 3) NOT NULL,
    created_at     TIMESTAMPTZ    NOT NULL DEFAULT now(),
    CONSTRAINT uq_appointment_products_id_center    UNIQUE (id, center_id),
    CONSTRAINT uq_appointment_products_appt_product UNIQUE (appointment_id, product_id),
    CONSTRAINT fk_appt_products_appointment         FOREIGN KEY (appointment_id, center_id)
        REFERENCES appointments (id, center_id) ON DELETE CASCADE,
    CONSTRAINT fk_appt_products_product             FOREIGN KEY (product_id, center_id)
        REFERENCES products (id, center_id) ON DELETE CASCADE
);
CREATE INDEX idx_appointment_products_center_appt    ON appointment_products (center_id, appointment_id);
CREATE INDEX idx_appointment_products_center_product ON appointment_products (center_id, product_id);

CREATE TABLE audit_logs (
    id            BIGSERIAL    PRIMARY KEY,
    actor_user_id INT,
    center_id     INT,
    plan_id       INT,
    action        VARCHAR(60)  NOT NULL,
    metadata      JSONB,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT fk_audit_logs_actor  FOREIGN KEY (actor_user_id) REFERENCES users (id)   ON DELETE SET NULL,
    CONSTRAINT fk_audit_logs_center FOREIGN KEY (center_id)     REFERENCES centers (id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_logs_plan   FOREIGN KEY (plan_id)       REFERENCES plans (id)   ON DELETE SET NULL
);
CREATE INDEX idx_audit_logs_center ON audit_logs (center_id, created_at DESC);
CREATE INDEX idx_audit_logs_action ON audit_logs (action, created_at DESC);
