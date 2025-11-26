-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 01:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `soknadsystemdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','reviewed','accepted','rejected') DEFAULT 'pending',
  `application_date` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  `cv_document_id` int(11) NOT NULL,
  `cover_letter_document_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('cv','cover_letter') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `amount` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `resource_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `creator_id`, `title`, `department`, `location`, `amount`, `description`, `resource_url`, `created_at`, `updated_at`) VALUES
(22, 72, 'LA - MUR-118 Musikkproduksjon for elektronisk musikk', 'Kunstfag', 'Kristiansand', 1, 'Emnet legger vekt på innspilling og miksing av musikk, bruk av analoge og digitale synthesizere og midi-programmering/beatmaking. Studentene arbeider med å utvikle egne arbeidsrutiner i forhold til kunstneriske vurderinger og kritisk lytting. I musikkproduksjon er det viktig for studenten å ha kunnskap om relevante lydteorier og internasjonale fagbegreper som brukes i et lydstudio. Det gis også en innføring i grunnleggende signalteori og akustikk.', 'https://www.uia.no/studier/emner/2025/host/mur118.html', '2025-10-25 11:19:31', '2025-11-25 11:31:56'),
(23, 72, 'LA - IS-110 Objektorientert programmering', 'Samfunnsvitenskap', 'Kristiansand', 6, 'Emnet gir en oversikt over objektorientert programmering, og grunnbegreper som klasser, objekter og metoder, samt mer avanserte begreper som arv (sub- og superklasser, multippel arv og abstrakte klasser). Imperativ programmering, og begreper som tilordning, if-setninger og løkker blir også gjennomgått. I tillegg til trenging i programmering får studentene prøve seg på bruk av testverktøy, bruk av kodestandarder og detaljdesign.', 'https://www.uia.no/studier/emner/2026/var/is-110.html', '2025-10-25 11:20:27', '2025-11-25 11:45:53'),
(24, 72, 'LA - MM-109 Game History', 'Teknologi og realfag', 'Grimstad', 1, 'Games have played a central role in people\'s lives for millennia. All civilizations have used games in one form or another. Educational systems also use and mirror games as an integral part of teaching. The ongoing development of digital games and technology makes it possible to play together regardless of physical presence. Because of this, separate directions have developed within these segments of digital games. E-sports is one such direction of interest.\r\n\r\nThis course will provide an insight into the historical development of games and the impact on society, from simple holes in the ground to endless cloud-based games. The course will also introduce different types of games that students will become better acquainted with.', 'https://www.uia.no/english/studies/courses/2025/autumn/mm-109.html', '2025-10-25 11:22:20', '2025-11-25 11:47:21'),
(51, 74, 'LA - MA-143 Biostatistikk', 'Teknologi og realfag', 'Kristiansand', 2, 'Deskriptiv statistikk, sannsynlighet og sannsynlighetsfordelinger, konfidensintervall og hypotesetesting mht en parameter og forskjeller mellom to parametre, variananalyse, lineær regresjon, kjikvadrattester – samt anvendelser mht arbeid på laboratoriet og i felt.', 'https://www.uia.no/studier/emner/2026/var/ma-143.html', '2025-11-25 11:12:28', '2025-11-25 11:49:06'),
(52, 74, 'LA - MM-206 Applikasjonsutvikling 1', 'Teknologi og realfag', 'Grimstad', 3, 'Klientprogrammering med CSS/HTML/JavaScript, design av brukergrensesnitt og interaksjon, datautveksling og serverkommunikasjon.', 'https://www.uia.no/studier/emner/2025/host/mm-206.html', '2025-11-25 11:18:12', '2025-11-25 11:48:44'),
(53, 74, 'LA - JUR-210 Rettsstaten', 'Handelshøyskolen ved UiA', 'Kristiansand', 8, 'Grunnloven er grunnlaget både for det norske rettssystemet og for norsk deltagelse i forpliktende internasjonalt samarbeid (§§ 1, 26, 115). Grunnloven omhandler statsmaktenes kompetanse og forholdet mellom disse. Den setter rettslig ramme for det norske demokratiet, samtidig som den pålegger demokratisk maktutøvelse å skje innenfor rettslige rammer og begrenset av menneskerettighetene, nedfelt i Grunnlovens \"rettighetskatalog\". Gjennom menneskerettsloven fra 1999 er Den europeiske menneskerettskonvensjonen (EMK) og fire FN-konvensjoner om menneskerettigheter gjort til norsk lov med forrang framfor annen lovgiving. Disse konvensjonene påvirker ikke bare tolkingen av norsk lov på mange rettsområder, men også av parallelle rettigheter i den norske grunnloven. Gjennom EØS-avtalen som gjør Norge til del av EUs indre marked er over 10.000 rettsakter fra EU tatt inn i norsk rett. EØS-retten er med andre ord meget omfattende, og den kommer til utslag på de fleste rettsområder. EØS-avtalen både påvirker og utfordrer Grunnlovens forutsetning om at Stortinget er lovgiver, at regjeringen er utøvende makt og at Høyesterett dømmer i siste instans.\r\n\r\nNorsk rett utvikles ikke bare av norske demokratiske institusjoner, men også i et dynamisk samspill mellom norske institusjoner og ulike internasjonale håndhevingsorganer og internasjonale domstoler som Den europeiske menneskerettsdomstolen (EMD), EU-domstolen og EFTA-domstolen. I dag er norsk rett sammenvevd med en rekke folkerettslige avtaler med ulik rettslig status, lovgivningsteknikk, rettskildebilde og tolkingsmetode. Denne kompleksiteten skaper særskilte metodiske utfordringer for dagens og framtidens jurister.\r\n\r\nMed utgangspunkt i rettsstaten og menneskerettighetene tar emnet opp utvalgte institusjonelle, metodiske og materielle sider ved norsk statsrett og ulike former for internasjonal rett (heri særlig EMK og EU/EØS-retten). Formålet med emnet er å gjøre studentene i stand til å håndtere det komplekse forholdet mellom Grunnloven, lov og ulike folkerettslige instrumenter som er særlig viktige for norsk rett. EØS-avtalen og EMK står i en særstilling fordi disse to avtalene griper bredere og dypere inn i norsk rett og samfunnsliv enn noen andre folkerettslige avtaler Norge har inngått.', 'https://www.uia.no/studier/emner/2025/host/jur210.html', '2025-11-25 11:19:54', '2025-11-25 11:58:57'),
(54, 72, 'LA - IKT-222 Softwaresikkerhet', 'Teknologi og realfag', 'Grimstad', 2, 'Dette emnet introduserer studenten for det grunnleggende om programvaresikkerhet. Studenten lærer om måter å forebygge og styrke sikkerheten i programmer og hvordan man feilsøker programkode for å identifisere mulige sårbarheter. Studenten lærer flere banebrytende tilnærminger for \"fuzz testing\" programkode for sikkerhetsfeil.', 'https://www.uia.no/studier/emner/2025/host/ikt222.html', '2025-11-25 11:28:16', '2025-11-25 12:02:31'),
(55, 72, 'LA - MUK-178 Musikkteknologi', 'Kunstfag', 'Kristiansand', 1, 'Emnet omfatter:\r\ninnføring i bruk av lydstudio for lydopptak og redigering\r\ninnføring i grunnleggende mikrofonteknikk, lyd- og akustikkteori\r\ninnføring i relevant musikkteknologisk utstyr for lydbehandling\r\ninnføring i bruk av ulike programmer og programtillegg', 'https://www.uia.no/studier/emner/2025/host/muk178.html', '2025-11-25 12:01:54', '2025-11-25 12:01:54'),
(56, 74, 'LA - IS-218 Geografiske Informasjonssystemer, AI og IoT', 'Samfunnsvitenskap', 'Kristiansand', 3, 'Dette emnet gir studentene en grundig forståelse av samspillet mellom geografisk informasjonssystem (GIS), kunstig intelligens (KI) og tingenes internett (IoT). Gjennom praktiske øvelser og forelesninger vil studentene utforske geografisk informasjon, standarder for GIS, referansesystemer og metoder for datainnsamling. Studentene vil lære å anvende kunstig intelligens og IoT-teknologier sammen med GIS. Emnet legger vekt på å utvikle praktiske ferdigheter og analytisk tilnærming for å løse komplekse geografiske utfordringer i den digitale tidsalderen. ', 'https://www.uia.no/studier/emner/2026/var/is-218.html', '2025-11-25 12:08:50', '2025-11-25 12:09:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('student','employee','admin') NOT NULL DEFAULT 'student',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `failed_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `reset_token`, `reset_token_expires_at`, `email`, `full_name`, `phone`, `role`, `is_active`, `created_at`, `updated_at`, `failed_attempts`, `lockout_until`) VALUES
(72, 'ansatt', '$2y$12$41guaOSFi0/qL4nO7KLlkO6ymjSsGlG1D5PvngV.MRIaplr.YXO2G', NULL, NULL, 'ansatt@test.no', NULL, NULL, 'employee', 1, '2025-10-25 11:11:26', '2025-11-02 12:37:02', 0, NULL),
(74, 'admin', '$2y$12$LVAeZJqx03W7mcR3Q0039uO5r2Ygyhx.VmvlTNOkZGMGfMbhGtFPC', NULL, NULL, 'admin@test.no', NULL, NULL, 'admin', 1, '2025-10-25 11:13:36', '2025-11-25 11:22:28', 0, NULL),
(89, 'student', '$2y$12$oLOOQWIFgAAeLpTiDDXd7OQX/JVu0cnezIO9DvZo3fWj45zdCA0PW', NULL, NULL, 'student@test.no', NULL, NULL, 'student', 1, '2025-10-31 19:52:25', '2025-10-31 19:52:25', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`position_id`,`user_id`),
  ADD KEY `fk_application_user` (`user_id`),
  ADD KEY `fk_application_cv_document` (`cv_document_id`),
  ADD KEY `fk_application_cover_letter_document` (`cover_letter_document_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_position_creator` (`creator_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=272;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_application_cover_letter_document` FOREIGN KEY (`cover_letter_document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_application_cv_document` FOREIGN KEY (`cv_document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_application_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_application_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `fk_position_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
