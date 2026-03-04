CREATE TABLE students(
	student_id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    passwd VARCHAR(255) NOT NULL,
    f_name VARCHAR(255) NOT NULL,
    m_name VARCHAR(255),
    l_name VARCHAR(255),
    address VARCHAR(255) NOT NULL,
    birth_day DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20)
);

CREATE TABLE services (
    service_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    service_title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON DELETE CASCADE
);

CREATE TABLE req_service (
    request_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    service_id INT UNSIGNED NOT NULL,
    requester_id INT UNSIGNED NOT NULL,
    
    status ENUM('pending', 'accepted', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (service_id) REFERENCES services(service_id)
        ON DELETE CASCADE,
        
    FOREIGN KEY (requester_id) REFERENCES students(student_id)
        ON DELETE CASCADE
);

CREATE TABLE products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNSIGNED NOT NULL,
    product_title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    status ENUM('available', 'reserved', 'sold') NOT NULL DEFAULT 'available',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (owner_id) REFERENCES students(student_id)
        ON DELETE CASCADE
);

CREATE TABLE buy_product (
    buy_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    product_id INT UNSIGNED NOT NULL,
    buyer_id INT UNSIGNED NOT NULL,
    
    bought_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE CASCADE,
        
    FOREIGN KEY (buyer_id) REFERENCES students(student_id)
        ON DELETE CASCADE
);


CREATE TABLE companies (
	company_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    passwd VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20)
);

CREATE TABLE jobs (
    job_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    description TEXT,
    salary DECIMAL(10,2),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (company_id) REFERENCES companies(company_id)
        ON DELETE CASCADE
);

CREATE TABLE apply_job (
    application_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    job_id INT UNSIGNED NOT NULL,
    applicant_id INT UNSIGNED NOT NULL,
    
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE,
        
    FOREIGN KEY (applicant_id) REFERENCES students(student_id)
        ON DELETE CASCADE
);


CREATE TABLE reviews (
    review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    reviewer_id INT UNSIGNED NOT NULL,
    
    service_id INT UNSIGNED,
    product_id INT UNSIGNED,
    company_id INT UNSIGNED,
    
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reviewer_id) REFERENCES students(student_id)
        ON DELETE CASCADE,
        
    FOREIGN KEY (service_id) REFERENCES services(service_id)
        ON DELETE CASCADE,
        
    FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE CASCADE,
        
    FOREIGN KEY (company_id) REFERENCES companies(company_id)
        ON DELETE CASCADE,
        
    CHECK (
        (service_id IS NOT NULL AND product_id IS NULL AND company_id IS NULL)
        OR
        (service_id IS NULL AND product_id IS NOT NULL AND company_id IS NULL)
        OR
        (service_id IS NULL AND product_id IS NULL AND company_id IS NOT NULL)
    )
);


INSERT INTO `students` (`student_id`, `username`, `passwd`, `f_name`, `m_name`, `l_name`, `address`, `birth_day`, `created_at`, `email`, `phone`) VALUES
(NULL, 'test', 'test', 'test', NULL, NULL, 'dhk', '2016-03-03', current_timestamp(), 'test@test.com', '01955994096');


INSERT INTO `products` (`product_id`, `owner_id`, `product_title`, `description`, `price`, `status`, `created_at`) VALUES 
(NULL, '1', 'Notes', 'CSE311 Notes', '500', 'available', current_timestamp());


INSERT INTO `services` (`service_id`, `student_id`, `service_title`, `description`, `price`, `created_at`) VALUES 
(NULL, '1', 'CSE311 Tutoring', 'Expert in ERD, Normalization and SQL', '1200', current_timestamp());

INSERT INTO `companies` (`company_id`, `username`, `passwd`, `name`, `address`, `created_at`, `email`, `phone`) VALUES 
(NULL, 'pathao_u', 'pathao', 'Pathao Ltd.', 'dhk', current_timestamp(), 'pathao@pathao.com', '01955994088');

INSERT INTO `jobs` (`job_id`, `company_id`, `job_title`, `description`, `salary`, `created_at`) VALUES 
(NULL, '1', 'Junior Software Engineer', 'Expirenced in Java & Python', '30000', current_timestamp());