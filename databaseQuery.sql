CREATE TABLE stock_events (
    id INT PRIMARY KEY auto_increment,
    event_id VARCHAR(45),
    sku VARCHAR(20),
    delta INT,
    source ENUM('erp', 'prestashop', 'magento'),
    created_at TIMESTAMP default current_timestamp,
    processed_at TIMESTAMP default current_timestamp
);
