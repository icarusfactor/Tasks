CREATE TABLE vol6tasks (
    page_id INT NOT NULL DEFAULT 0,
    STATUS ENUM('!','1','2','3',' ','x','?') NOT NULL DEFAULT ' ',
    owner VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    hidden ENUM('y','n') NOT NULL DEFAULT 'n',
    KEY owner_idx (owner)
);