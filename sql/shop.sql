
-----------------------------------------------------------------------
-- user
-----------------------------------------------------------------------

DROP TABLE IF EXISTS [user];

CREATE TABLE [user]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [firstname] VARCHAR(255) NOT NULL,
    [lastname] VARCHAR(255) NOT NULL,
    [email] VARCHAR(255) NOT NULL,
    [password] VARCHAR(255) NOT NULL,
    [role] VARCHAR(255) DEFAULT 'customer' NOT NULL,
    UNIQUE ([id])
);

-----------------------------------------------------------------------
-- product
-----------------------------------------------------------------------

DROP TABLE IF EXISTS [product];

CREATE TABLE [product]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [name] VARCHAR(128) NOT NULL,
    [img] VARCHAR(128) NOT NULL,
    [unit] VARCHAR(10) NOT NULL,
    [description] VARCHAR(255),
    [unit_price] FLOAT NOT NULL,
    UNIQUE ([id])
);

-----------------------------------------------------------------------
-- ordertbl
-----------------------------------------------------------------------

DROP TABLE IF EXISTS [ordertbl];

CREATE TABLE [ordertbl]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [user_id] INTEGER NOT NULL,
    [datetime] TIMESTAMP DEFAULT (datetime(CURRENT_TIMESTAMP, 'localtime')),
    UNIQUE ([id]),
    FOREIGN KEY ([user_id]) REFERENCES [user] ([id])
);

-----------------------------------------------------------------------
-- orderposition
-----------------------------------------------------------------------

DROP TABLE IF EXISTS [orderposition];

CREATE TABLE [orderposition]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [order_id] INTEGER NOT NULL,
    [product_id] INTEGER NOT NULL,
    [quantity] INTEGER NOT NULL,
    UNIQUE ([id]),
    FOREIGN KEY ([order_id]) REFERENCES [ordertbl] ([id]),
    FOREIGN KEY ([product_id]) REFERENCES [product] ([id])
);
