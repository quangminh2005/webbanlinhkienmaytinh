USE pc_shop;

INSERT INTO categories (name, slug) VALUES
('SSD', 'ssd'),
('HDD', 'hdd'),
('Case', 'case'),
('Cooler', 'cooler'),
('Monitor', 'monitor'),
('Keyboard', 'keyboard'),
('Mouse', 'mouse')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (category_id, name, price, stock_quantity, description, socket, ram_type, vram_gb, wattage)
VALUES
((SELECT id FROM categories WHERE slug = 'cpu'), 'AMD Ryzen 5 7600', 5600000, 22, 'CPU 6 nhan 12 luong, socket AM5', 'AM5', '', 0, 65),
((SELECT id FROM categories WHERE slug = 'cpu'), 'Intel Core i7-14700K', 11200000, 12, 'CPU cao cap cho gaming va creator', 'LGA1700', '', 0, 125),

((SELECT id FROM categories WHERE slug = 'mainboard'), 'MSI B650M Mortar WiFi', 5200000, 14, 'Mainboard AM5 ho tro DDR5', 'AM5', 'DDR5', 0, 70),
((SELECT id FROM categories WHERE slug = 'mainboard'), 'Gigabyte Z790 Aorus Elite', 7800000, 8, 'Mainboard Z790 cho Intel gen 12/13/14', 'LGA1700', 'DDR5', 0, 80),

((SELECT id FROM categories WHERE slug = 'vga'), 'AMD Radeon RX 7800 XT 16GB', 14500000, 9, 'Card do hoa RX 7800 XT cho gaming 2K', '', '', 16, 263),
((SELECT id FROM categories WHERE slug = 'vga'), 'NVIDIA RTX 4060 Ti 8GB', 10900000, 16, 'Card do hoa tam trung RTX 4060 Ti', '', '', 8, 160),

((SELECT id FROM categories WHERE slug = 'ram'), 'Corsair Vengeance 32GB DDR5 5600', 3150000, 24, 'Bo RAM DDR5 2x16GB', '', 'DDR5', 0, 10),
((SELECT id FROM categories WHERE slug = 'ram'), 'G.Skill Ripjaws 16GB DDR4 3200', 1050000, 40, 'RAM DDR4 2x8GB', '', 'DDR4', 0, 8),

((SELECT id FROM categories WHERE slug = 'psu'), 'Seasonic Focus GX 850W', 3490000, 18, 'Nguon 850W 80 Plus Gold', '', '', 0, 850),
((SELECT id FROM categories WHERE slug = 'psu'), 'Cooler Master MWE 650 Bronze V2', 1790000, 28, 'Nguon 650W cho cau hinh tam trung', '', '', 0, 650),

((SELECT id FROM categories WHERE slug = 'ssd'), 'Samsung 990 PRO 1TB NVMe', 2890000, 25, 'SSD NVMe PCIe 4.0 toc do cao', '', '', 0, 6),
((SELECT id FROM categories WHERE slug = 'ssd'), 'WD Black SN770 2TB', 3290000, 15, 'SSD NVMe 2TB cho gaming', '', '', 0, 6),

((SELECT id FROM categories WHERE slug = 'hdd'), 'Seagate Barracuda 2TB 7200RPM', 1390000, 30, 'HDD luu tru du lieu dung luong lon', '', '', 0, 8),
((SELECT id FROM categories WHERE slug = 'hdd'), 'WD Blue 1TB 7200RPM', 980000, 35, 'HDD pho thong cho PC van phong', '', '', 0, 6),

((SELECT id FROM categories WHERE slug = 'case'), 'NZXT H5 Flow', 2290000, 12, 'Vo case mid tower thoang khi', '', '', 0, 0),
((SELECT id FROM categories WHERE slug = 'case'), 'Lian Li Lancool 216', 2450000, 10, 'Case gaming ho tro tan nhiet tot', '', '', 0, 0),

((SELECT id FROM categories WHERE slug = 'cooler'), 'Deepcool AK620', 1490000, 18, 'Tan nhiet khi thap doi hieu nang cao', '', '', 0, 5),
((SELECT id FROM categories WHERE slug = 'cooler'), 'Corsair iCUE H100i RGB', 3290000, 11, 'Tan nhiet nuoc AIO 240mm', '', '', 0, 10),

((SELECT id FROM categories WHERE slug = 'monitor'), 'LG UltraGear 27GP850 27 inch 2K 165Hz', 7890000, 14, 'Man hinh gaming 2K IPS 165Hz', '', '', 0, 35),
((SELECT id FROM categories WHERE slug = 'monitor'), 'ASUS TUF VG249Q3A 24 inch 180Hz', 4290000, 20, 'Man hinh gaming Full HD 180Hz', '', '', 0, 25),

((SELECT id FROM categories WHERE slug = 'keyboard'), 'Keychron K8 Pro', 2490000, 21, 'Ban phim co wireless hot-swap', '', '', 0, 2),
((SELECT id FROM categories WHERE slug = 'keyboard'), 'Akko 3068B Plus', 1890000, 26, 'Ban phim co 68 phim ket noi 3 che do', '', '', 0, 2),

((SELECT id FROM categories WHERE slug = 'mouse'), 'Logitech G Pro X Superlight 2', 3290000, 17, 'Chuot gaming khong day trong luong nhe', '', '', 0, 2),
((SELECT id FROM categories WHERE slug = 'mouse'), 'Razer DeathAdder V3', 1790000, 29, 'Chuot gaming ergonomic hieu nang cao', '', '', 0, 2);

