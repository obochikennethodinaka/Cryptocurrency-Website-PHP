-- CryptoNexus Database
-- Import this file via phpMyAdmin: click Import > Choose File > select this file > Go

CREATE DATABASE IF NOT EXISTS cryptonexus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cryptonexus;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) DEFAULT '',
    is_verified TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    currency VARCHAR(10) NOT NULL,
    balance DECIMAL(28,10) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq (user_id, currency)
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tx_hash VARCHAR(100),
    type ENUM('buy','sell','send','receive','deposit','withdraw') NOT NULL,
    currency_from VARCHAR(10),
    currency_to VARCHAR(10),
    amount DECIMAL(28,10) NOT NULL,
    price_usd DECIMAL(20,4) DEFAULT 0,
    fee DECIMAL(28,10) DEFAULT 0,
    status ENUM('pending','completed','failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS price_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    currency VARCHAR(10) NOT NULL,
    target_price DECIMAL(20,4) NOT NULL,
    cond ENUM('above','below') NOT NULL,
    is_triggered TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    category VARCHAR(50) DEFAULT 'News',
    is_published TINYINT(1) DEFAULT 1,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS crypto_coins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    coingecko_id VARCHAR(100),
    current_price_usd DECIMAL(20,4) DEFAULT 0,
    rank INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
);

INSERT IGNORE INTO crypto_coins (symbol,name,coingecko_id,rank) VALUES
('BTC','Bitcoin','bitcoin',1),('ETH','Ethereum','ethereum',2),('USDT','Tether','tether',3),
('BNB','BNB','binancecoin',4),('SOL','Solana','solana',5),('XRP','XRP','ripple',6),
('ADA','Cardano','cardano',7),('AVAX','Avalanche','avalanche-2',8),('DOGE','Dogecoin','dogecoin',9),
('DOT','Polkadot','polkadot',10),('MATIC','Polygon','matic-network',11),('LINK','Chainlink','chainlink',12),
('LTC','Litecoin','litecoin',13),('UNI','Uniswap','uniswap',14),('ATOM','Cosmos','cosmos',15);

INSERT IGNORE INTO articles (title,excerpt,category) VALUES
('Bitcoin Reaches New All-Time High Amid Institutional Buying','Bitcoin surged to unprecedented levels as institutional investors continue accumulating BTC in record numbers.','Market News'),
('Ethereum 2.0: What You Need to Know','A comprehensive guide to understanding Ethereum staking and the transition to proof-of-stake.','Education'),
('Top DeFi Protocols to Watch in 2025','Decentralized Finance continues to evolve. Here are the protocols dominating the space.','DeFi'),
('How to Secure Your Crypto Wallet','Security is paramount in crypto. Learn how to protect your digital assets from common threats.','Security'),
('Regulatory Clarity: SEC Updates Crypto Guidelines','New guidance from regulators provides clearer frameworks for cryptocurrency businesses.','Regulation');

-- Demo accounts created by first-run.php (password: Demo@12345)
-- Run first-run.php after importing this file to set up accounts automatically
