-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 16, 2025 at 01:28 PM
-- Server version: 12.1.2-MariaDB
-- PHP Version: 8.5.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uaspwkakhir`
--

-- --------------------------------------------------------

--
-- Table structure for table `BUKU`
--

CREATE TABLE `BUKU` (
  `id_buku` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pengarang` varchar(255) NOT NULL,
  `penerbit` varchar(255) NOT NULL,
  `tahun_terbit` year(4) DEFAULT NULL,
  `stok_total` int(11) NOT NULL DEFAULT 0,
  `stok_tersedia` int(11) NOT NULL DEFAULT 0,
  `isbn` varchar(50) DEFAULT NULL,
  `cover_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `BUKU`
--

INSERT INTO `BUKU` (`id_buku`, `judul`, `pengarang`, `penerbit`, `tahun_terbit`, `stok_total`, `stok_tersedia`, `isbn`, `cover_path`) VALUES
(1, 'Home Sweet Loan', 'Almira Bastari', 'Gramedia Pustaka Utama', '2022', 50, 50, '9786020658049', 'assets/covers/cover_edit_6941499db15cd.jpg'),
(2, 'Kemunculan Komunisme Indonesia', 'Ruth McVey', 'Cornell University Press', '1965', 30, 29, '9781501742651', 'assets/covers/cover_edit_69414b27052c6.png'),
(3, 'HTML 5 & CSS 3', 'R.H Sianipar', 'INFORMATIKA', '2015', 10, 10, '9786021514672', 'assets/covers/cover_edit_6941494d3648c.jpg'),
(4, 'Boost Your Potentials!', 'Ferdhy Febryan', 'MQS PUBLISHING', '2010', 30, 30, '9789793373691', 'assets/covers/cover_edit_69414bd79fcaf.png'),
(5, 'Bagaimana Membangun Kepribadian Anda', 'Khalil Al-Musawi', 'PT. LENTERA BASRITAMA', '2002', 15, 15, '9798880447', 'assets/covers/cover_edit_69414ae0f24c0.png'),
(6, 'Rascal Does Not Dream of Bunny Girl Senpai', 'Hajime Kamoshida', 'Dengeki Bunko', '2014', 50, 50, '978-4048669443', 'assets/covers/cover_edit_69414cb81563b.png'),
(7, 'Oresuki: Are You the Only One Who Loves Me?', 'Rakuda', 'Dengeki Bunko', '2016', 20, 20, '978-4048658423', 'assets/covers/cover_edit_69414e1fa9c1a.png'),
(8, 'Bloom Into You: Concerning Saeki Sayaka', 'Hitomi Iruma', 'Dengeki Bunko', '2018', 30, 30, '978-4048937932', 'assets/covers/cover_edit_69414b902162b.png'),
(9, 'The Angel Next Door Spoils Me Rotten', 'Saekisan', 'GA Bunko', '2019', 50, 48, '978-4815602418', 'assets/covers/cover_edit_69414e2f9da3e.png'),
(10, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', '2005', 30, 30, '979-3062-79-7', 'assets/covers/cover_edit_69414d5bc8bb1.png'),
(11, 'Surat Kecil Untuk Tuhan', 'Agnes Davonar', 'Inandra Published', '2008', 20, 20, '978-979-1383-05-9', 'assets/covers/cover_edit_69414e103ea7e.png'),
(12, '5 cm', 'Donny Dhirgantoro', 'Grasindo', '2007', 30, 30, '978-979-759-052-1', 'assets/covers/cover_edit_69414a631c152.png'),
(13, 'Eiffel I\'m In Love', 'Rachmania Arunita', 'Gramedia Pustaka Utama', '2003', 40, 40, '979-2200-11-2', 'assets/covers/cover_edit_69414c8909850.png'),
(14, 'Rudy: Kisah Masa Muda B.J. Habibie', 'Gina S. Noer', 'Bentang Pustaka', '2015', 10, 10, '978-602-291-039-3', 'assets/covers/cover_edit_69414e0155189.png');

-- --------------------------------------------------------

--
-- Table structure for table `DETAIL_PEMINJAMAN`
--

CREATE TABLE `DETAIL_PEMINJAMAN` (
  `id_detail` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `jumlah_pinjam` int(11) NOT NULL DEFAULT 1,
  `denda` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `DETAIL_PEMINJAMAN`
--

INSERT INTO `DETAIL_PEMINJAMAN` (`id_detail`, `id_peminjaman`, `id_buku`, `jumlah_pinjam`, `denda`) VALUES
(1, 1, 1, 1, 0.00),
(2, 2, 1, 1, 0.00),
(5, 5, 9, 1, 0.00),
(6, 6, 9, 1, 0.00),
(7, 7, 2, 1, 0.00),
(8, 8, 3, 1, 0.00),
(9, 9, 5, 1, 0.00),
(10, 10, 2, 1, 0.00),
(11, 11, 9, 1, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `MAHASISWA`
--

CREATE TABLE `MAHASISWA` (
  `id_mahasiswa` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nim` varchar(15) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `fakultas` varchar(100) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `angkatan` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `MAHASISWA`
--

INSERT INTO `MAHASISWA` (`id_mahasiswa`, `id_user`, `nim`, `nama_lengkap`, `fakultas`, `jurusan`, `angkatan`) VALUES
(1, 2, '532424069', 'Ramdan Olii', 'Teknik', 'Informatika', '2024'),
(3, 7, '532424086', 'Israwaty Husain', 'Teknik', 'Teknik Informatika', '2024'),
(4, 8, '123456789', 'Nyantax', 'Teknik', 'Informatika', '2025');

-- --------------------------------------------------------

--
-- Table structure for table `PEMINJAMAN`
--

CREATE TABLE `PEMINJAMAN` (
  `id_peminjaman` int(11) NOT NULL,
  `id_mahasiswa` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali_harus` date NOT NULL,
  `tanggal_kembali_aktual` date DEFAULT NULL,
  `status_pinjam` varchar(20) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `PEMINJAMAN`
--

INSERT INTO `PEMINJAMAN` (`id_peminjaman`, `id_mahasiswa`, `tanggal_pinjam`, `tanggal_kembali_harus`, `tanggal_kembali_aktual`, `status_pinjam`) VALUES
(1, 1, '2025-12-14', '2025-12-21', '2025-12-14', 'Dikembalikan'),
(2, 1, '2025-12-14', '2025-12-21', '2025-12-14', 'Dikembalikan'),
(5, 1, '2025-12-15', '2025-12-22', '2025-12-15', 'Dikembalikan'),
(6, 1, '2025-12-15', '2025-12-22', NULL, 'Dipinjam'),
(7, 1, '2025-12-15', '2025-12-22', '2025-12-16', 'Dikembalikan'),
(8, 1, '2025-12-15', '2025-12-22', NULL, 'Ditolak'),
(9, 3, '2025-12-16', '2025-12-23', '2025-12-16', 'Dikembalikan'),
(10, 3, '2025-12-16', '2025-12-23', NULL, 'Dipinjam'),
(11, 4, '2025-12-16', '2025-12-23', NULL, 'Dipinjam');

-- --------------------------------------------------------

--
-- Table structure for table `ROLE`
--

CREATE TABLE `ROLE` (
  `id_role` int(11) NOT NULL,
  `nama_role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ROLE`
--

INSERT INTO `ROLE` (`id_role`, `nama_role`) VALUES
(1, 'Admin'),
(2, 'Kepala Perpustakaan'),
(3, 'Mahasiswa');

-- --------------------------------------------------------

--
-- Table structure for table `USER`
--

CREATE TABLE `USER` (
  `id_user` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `USER`
--

INSERT INTO `USER` (`id_user`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'adminperpus', 'admin@perpus.com', '482c811da5d5b4bc6d497ffa98491e38', '2025-12-14 07:12:25'),
(2, 'ramdan', 'ramdanolii1410@gmail.com', '1b5f0dffdcdaac1028a53fa6ee419acb', '2025-12-14 07:12:41'),
(6, 'Kazuha', 'kazuha@gmail.com', '4afd66715b2ff53d756c7a39bb8ac472', '2025-12-14 10:26:53'),
(7, 'Ichahusain11', 'Ichahusain11@gmail.com', '70f3ebfc2ae0b86cd4cdabdad9e61ed4', '2025-12-16 06:13:18'),
(8, 'nyanta', 'nyanta@gmail.com', '6c603ff36881476752e220195bf494b8', '2025-12-16 12:20:18');

-- --------------------------------------------------------

--
-- Table structure for table `USER_ROLE`
--

CREATE TABLE `USER_ROLE` (
  `id_user_role` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_role` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `USER_ROLE`
--

INSERT INTO `USER_ROLE` (`id_user_role`, `id_user`, `id_role`) VALUES
(1, 1, 1),
(2, 2, 3),
(5, 6, 1),
(6, 7, 3),
(7, 8, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `BUKU`
--
ALTER TABLE `BUKU`
  ADD PRIMARY KEY (`id_buku`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `DETAIL_PEMINJAMAN`
--
ALTER TABLE `DETAIL_PEMINJAMAN`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_peminjaman` (`id_peminjaman`),
  ADD KEY `id_buku` (`id_buku`);

--
-- Indexes for table `MAHASISWA`
--
ALTER TABLE `MAHASISWA`
  ADD PRIMARY KEY (`id_mahasiswa`),
  ADD UNIQUE KEY `id_user` (`id_user`),
  ADD UNIQUE KEY `nim` (`nim`);

--
-- Indexes for table `PEMINJAMAN`
--
ALTER TABLE `PEMINJAMAN`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`);

--
-- Indexes for table `ROLE`
--
ALTER TABLE `ROLE`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `nama_role` (`nama_role`);

--
-- Indexes for table `USER`
--
ALTER TABLE `USER`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `USER_ROLE`
--
ALTER TABLE `USER_ROLE`
  ADD PRIMARY KEY (`id_user_role`),
  ADD UNIQUE KEY `unique_user_role` (`id_user`,`id_role`),
  ADD KEY `id_role` (`id_role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `BUKU`
--
ALTER TABLE `BUKU`
  MODIFY `id_buku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `DETAIL_PEMINJAMAN`
--
ALTER TABLE `DETAIL_PEMINJAMAN`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `MAHASISWA`
--
ALTER TABLE `MAHASISWA`
  MODIFY `id_mahasiswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `PEMINJAMAN`
--
ALTER TABLE `PEMINJAMAN`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ROLE`
--
ALTER TABLE `ROLE`
  MODIFY `id_role` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `USER`
--
ALTER TABLE `USER`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `USER_ROLE`
--
ALTER TABLE `USER_ROLE`
  MODIFY `id_user_role` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `DETAIL_PEMINJAMAN`
--
ALTER TABLE `DETAIL_PEMINJAMAN`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_peminjaman`) REFERENCES `PEMINJAMAN` (`id_peminjaman`) ON DELETE CASCADE,
  ADD CONSTRAINT `2` FOREIGN KEY (`id_buku`) REFERENCES `BUKU` (`id_buku`);

--
-- Constraints for table `MAHASISWA`
--
ALTER TABLE `MAHASISWA`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_user`) REFERENCES `USER` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `PEMINJAMAN`
--
ALTER TABLE `PEMINJAMAN`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_mahasiswa`) REFERENCES `MAHASISWA` (`id_mahasiswa`) ON DELETE CASCADE;

--
-- Constraints for table `USER_ROLE`
--
ALTER TABLE `USER_ROLE`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_user`) REFERENCES `USER` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `2` FOREIGN KEY (`id_role`) REFERENCES `ROLE` (`id_role`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
