-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: atom_assistant
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ai_models`
--

DROP TABLE IF EXISTS `ai_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_models` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `api_endpoint` varchar(500) DEFAULT NULL,
  `api_key` varchar(500) DEFAULT NULL,
  `is_local` tinyint(4) NOT NULL DEFAULT 0,
  `is_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `context_length` int(11) NOT NULL DEFAULT 4096,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_models`
--

LOCK TABLES `ai_models` WRITE;
/*!40000 ALTER TABLE `ai_models` DISABLE KEYS */;
INSERT INTO `ai_models` VALUES (13,'llama3.1','Ollama','http://localhost:11434/api/chat','',1,1,131072,'2026-07-29 07:15:55'),(14,'llama3.2','Ollama','http://localhost:11434/api/chat','',1,1,131072,'2026-07-29 07:15:56'),(15,'mistral','Ollama','http://localhost:11434/api/chat','',1,1,32768,'2026-07-29 07:15:56'),(16,'Local Server','LM Studio','http://localhost:1234/v1/chat/completions','',1,1,4096,'2026-07-29 07:15:56'),(17,'Local Model','GPT4All','','',1,1,8192,'2026-07-29 07:15:56'),(18,'GGUF Runtime','llama.cpp','http://localhost:8080/v1/chat/completions','',1,1,4096,'2026-07-29 07:15:56'),(20,'Atom','local','http://localhost:11434/api/chat','',1,1,4096,'2026-08-03 06:42:43');
/*!40000 ALTER TABLE `ai_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_biometric_settings`
--

DROP TABLE IF EXISTS `atom_biometric_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_biometric_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `face_data_enabled` tinyint(4) DEFAULT 0,
  `face_photo_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_biometric_settings`
--

LOCK TABLES `atom_biometric_settings` WRITE;
/*!40000 ALTER TABLE `atom_biometric_settings` DISABLE KEYS */;
INSERT INTO `atom_biometric_settings` VALUES (1,0,'','2026-08-03 20:08:15','2026-08-03 20:08:15');
/*!40000 ALTER TABLE `atom_biometric_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_document_chunks`
--

DROP TABLE IF EXISTS `atom_document_chunks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_document_chunks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `page_number` int(11) NOT NULL,
  `section_title` varchar(255) DEFAULT NULL,
  `chunk_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  FULLTEXT KEY `chunk_text` (`chunk_text`),
  CONSTRAINT `atom_document_chunks_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `atom_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_document_chunks`
--

LOCK TABLES `atom_document_chunks` WRITE;
/*!40000 ALTER TABLE `atom_document_chunks` DISABLE KEYS */;
INSERT INTO `atom_document_chunks` VALUES (3,3,1,NULL,'1 arXiv:2312.10997v5 [cs.CL] 27 Mar 2024','2026-08-08 20:16:31'),(4,3,2,NULL,'2 Fig. 1. T echnology tree of RA G research. The stages of in v olving RA G mainly include pre-training, \\002ne-t uning, and inference. W ith the emer gence of LLMs, research on RA G initially focused on le v eraging the po werful in conte xt learning abil ities of LLMs, primarily concentrating on the inference stage. Subsequent research has delv ed deeper , gradually inte grating more with the \\002ne-tuning of LLMs. Researchers ha v e also been e xploring w ays to enhance language models in the pre-training stage through','2026-08-08 20:16:31'),(5,3,3,NULL,'3 Fig. 2. A representati v e instance of the RA G process appl ied to question answering. It mainly consists of 3 steps. 1\\051 Inde xing. Documents are split into chunks, encoded into v ectors, and stored in a v ector database. 2\\051','2026-08-08 20:16:31'),(6,3,4,NULL,'4 Fig. 3. Comparison','2026-08-08 20:16:31'),(7,3,5,NULL,'5 aligns the te xt more closely with data distrib ution through iter - ati v e self-enhancement [18]. Routing in the RA G system na vig ates through di v erse data sources, selecting the optimal pathw ay for a query ,','2026-08-08 20:16:31'),(8,3,6,NULL,'6 T ABLE I S U M M A R Y O F R A G M E T H O D S CoG W ikipedia T e xt Phrase Pre-training Iterati v e DenseX F actoidW iki T e xt Proposition Inference Once EAR','2026-08-08 20:16:31'),(9,3,7,NULL,'7 Fig. 4. RA G compared with other model optimization','2026-08-08 20:16:31'),(10,3,8,NULL,'8 the appropriate in the','2026-08-08 20:16:31'),(11,3,9,NULL,'9 2\\051 Query T r ansformation: The core concept is to','2026-08-08 20:16:31'),(12,3,10,NULL,'10 introduces an inno v ati v e','2026-08-08 20:16:31'),(13,3,11,NULL,'11 Fig. 5. In addition to the most c ommon once from pre vious searches. Recursi v e','2026-08-08 20:16:31'),(14,3,12,NULL,'12 probability of generate d terms When the probability f alls belo w a certain threshold w ould acti v ates the','2026-08-08 20:16:31'),(15,3,13,NULL,'13 T ABLE II D O W N S T R E A M T A S K S A N D D A T A S E T S O F R A G T ask Sub T ask QA Single-hop Natural Qustion\\050NQ\\051 [30], [34], [42], [45], [50], [52], [59], [64], [82] [4], [22], [27], [40], [43], [54], [62], [71], [112] [44], [72] T ri viaQA\\050TQA\\051 [30], [34], [45], [50], [64] [27], [59], [62], [112] [25], [43], [44], [71], [72] SQuAD [20], [23], [30], [32], [45], [69], [112] W eb Questions\\050W ebQ\\051 [3], [4], [13], [30], [50], [68] PopQA [7], [25], [67] MS MARCO [4], [40], [52] Multi-hop HotpotQA [26], [31], [34], [47], [51], [61], [82] [14], [22], [27], [59], [62], [69], [71], [91] 2W ikiMultiHopQA [14], [24], [48], [59], [61], [91] MuSiQue [14], [51], [61], [91] Long-form QA ELI5 [27], [34], [43], [49], [51] Narrati v eQA\\050NQA\\051 [45], [60], [63], [123] ASQA','2026-08-08 20:16:31'),(16,3,13,NULL,', [59], [61], [91] MuSiQue [14], [51], [61], [91] Long-form QA ELI5 [27], [34], [43], [49], [51] Narrati v eQA\\050NQA\\051 [45], [60], [63], [123] ASQA [24], [57] QMSum\\050QM\\051 [60], [123] Domain QA Qasper [60], [63] CO VID-QA [35], [46] CMB Medical [81] Multi-Choice QA QuALITY [60], [63] ARC [25], [67] CommonsenseQA [58], [66] Graph QA GraphQA [84] Dialog Dialog Generation W izard of W ikipedia \\050W oW\\051 [13], [27], [34], [42] Personal Dialog KBP [74], [135] DuleMon [74] T ask-oriented Dialog CamRest [78], [79] Recommendation Amazon\\050T o ys,Sport,Beauty\\051 [39], [40] IE Ev ent Ar gument Extraction W ikiEv ent [13], [27], [37], [42] RAMS [36], [37] Relation Extraction T -REx [142] [27], [51] Reasoning Commonsense Reasoning HellaSw ag [20], [66] CoT Reasoning CoT Reasoning [27]','2026-08-08 20:16:31'),(17,3,13,NULL,'42] RAMS [36], [37] Relation Extraction T -REx [142] [27], [51] Reasoning Commonsense Reasoning HellaSw ag [20], [66] CoT Reasoning CoT Reasoning [27] Comple x Reasoning CSQA [55] Others Language Understanding MMLU [7], [27], [28], [42], [43], [47], [72] Language Modeling W ikiT e xt-103 [5], [29], [64], [71] Strate gyQA [14], [24], [48], [51], [55], [58] F act Checking/V eri\\002cation FEVER [4], [13], [27], [34], [42], [50] PubHealth [25], [67] T e xt Generation Biograph y [67] T e xt Summarization W ikiASP [24] XSum [17] T e xt Classi\\002cation V ioLens [19] TREC [33] Sentiment SST -2 [20], [33], [38] Code Search','2026-08-08 20:16:31'),(18,3,13,NULL,'raph y [67] T e xt Summarization W ikiASP [24] XSum [17] T e xt Classi\\002cation V ioLens [19] TREC [33] Sentiment SST -2 [20], [33], [38] Code Search','2026-08-08 20:16:31'),(19,3,14,NULL,'14 T ABLE III S U M M A R Y O F M E T R I C S A P P L I C A B L E F O R E V A L U A T I O N A S P E C T S O F R A G Conte xt Rele v ance F aithfulness Answer Rele v ance Noise Rob ustness Ne g ati v e Rejection Information Inte gration Counterf actual Rob ustness Accurac y X X X X X X X EM X Recall X Precision X X R-Rate X Cosine Similarity X Hit Rate X MRR X NDCG X BLEU X X X R OUGE/R OUGE-L X X X The speci\\002c e v al/core concepts rag triad/ 9 https://kimi.moonshot.cn are not constrained by conte xt. In f act, RA G still plays an irreplaceable role. On one h a nd , pro viding LLMs with a lar ge amount of conte xt at once will signi\\002cantly impact its inference speed, while chunk ed','2026-08-08 20:16:31'),(20,3,15,NULL,'15 T ABLE IV S U M M A R Y O F E V A L U A T I O N F R A M E W O R K S Ev aluation Framew ork Ev aluation T ar RGB y RECALL y Generation Quality Counterf actual Rob ustness R-Rate \\050Reappearance Rate\\051 RA GAS z ARES z T ruLens z CR UD y \\206 r epr esents a benc hmark, and \\207 r epr esents a tool. * denotes customized quantitative','2026-08-08 20:16:31'),(21,3,16,NULL,'16 Fig. 6. Summary of RA G ecosystem initial learning c urv e. 3\\051 Specialization - optimizing RA G to','2026-08-08 20:16:31'),(22,3,17,NULL,'17 The gro wing ecosystem of RA G is e videnced by the rise in RA G-centric AI applications and the continuous de v elopment of supporti v e tools. As RA G\' s application landsca pe broadens, there is a need to re\\002ne e v aluation','2026-08-08 20:16:31'),(23,3,18,NULL,'18 B. W ang, W . Ping, P . Xu, L. McAfee, Z. Liu, M. Shoe ybi, Y . Dong, O. K uchaie v , B. Li, C. Xiao','2026-08-08 20:16:31'),(24,3,19,NULL,'19 Langchain, \\223Recursi v ely split by character , \\224 https://p ython.langchain. com/docs/modules/data connection/document transformers/recursi v e te xt splitter, 2023. S. Y ang, \\223 Adv anced rag 01: Small-to- big','2026-08-08 20:16:31'),(25,3,20,NULL,'20 planner for personalized kno wledge-grounded dialogue, \\224 arXiv pr eprint arXiv:2310.08840 , 2023. \\227\\227, \\223Lar ge language models as source planner for personal- ized kno wledge-grounded dialogue, \\224 arXiv pr eprint arXiv:2310.08840 , 2023. X. Xu, Z. Gou, W . W u, Z.-Y . Niu, H. W u, H. W ang, and S. W ang, \\223Long time no see! open-domain con v ersation with long-term persona memory , \\224 arXiv pr eprint arXiv:2203.05797 , 2022. T .-H. W en, M. Gasic, N. Mrksic, L. M . Rojas-Barahona, P .-H. Su, S. Ultes, D. V andyk e, and S. Y oung, \\223Conditional generation and snapshot learning in neural dialogue systems, \\224 arXiv pr eprint arXiv:1606.03352 , 2016. R. He and J.','2026-08-08 20:16:31'),(26,3,20,NULL,'and S. Y oung, \\223Conditional generation and snapshot learning in neural dialogue systems, \\224 arXiv pr eprint arXiv:1606.03352 , 2016. R. He and J. McAule y , \\223Ups and do wns: Modeling the visual e v olution of f ashion trends with one-class collaborati v e \\002ltering, \\224 in pr oceedings of the 25th international confer ence on world wide web , 2016, pp. 507\\226517. S. Li, H. Ji, and J. Han, \\223Document-le v el e v ent ar gument e xtraction by conditional generation, \\224 arXiv pr eprint arXiv:2104.05919 , 2021. S. Ebner , P . Xia, R. Culkin, K. Ra wlins, and B. V an Durme, \\223Multi- sentence ar gument linking, \\224 arXiv pr eprint arXiv:1911.03766 , 2019. H. Elsahar , P . V ougiouklis, A. Remaci, C. Gra vier , J. Hare, F . Laforest, and E.','2026-08-08 20:16:31'),(27,3,20,NULL,'ar gument linking, \\224 arXiv pr eprint arXiv:1911.03766 , 2019. H. Elsahar , P . V ougiouklis, A. Remaci, C. Gra vier , J. Hare, F . Laforest, and E. Simperl, \\223T -re x: A lar ge scal e alignment of natural language with kno wledge base triples, \\224 in Pr oceedings of the Ele venth Inter - national Confer ence on Langua g e Resour ces and Evaluation \\050LREC 2018\\051 , 2018. O. Le vy , M. Seo, E. Choi, and L.','2026-08-08 20:16:31'),(28,3,20,NULL,'ngs of the Ele venth Inter - national Confer ence on Langua g e Resour ces and Evaluation \\050LREC 2018\\051 , 2018. O. Le vy , M. Seo, E. Choi, and L.','2026-08-08 20:16:31'),(29,3,21,NULL,'21 A. Y ang, A. Nagrani, P . H. Seo, A. Miech, J. Pont-T','2026-08-08 20:16:31');
/*!40000 ALTER TABLE `atom_document_chunks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_documents`
--

DROP TABLE IF EXISTS `atom_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `path` varchar(500) NOT NULL,
  `ai_summary` text DEFAULT NULL,
  `trained_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_documents`
--

LOCK TABLES `atom_documents` WRITE;
/*!40000 ALTER TABLE `atom_documents` DISABLE KEYS */;
INSERT INTO `atom_documents` VALUES (3,'1786220190_a5ce84c9c2710b94156a','1786220190_a5ce84c9c2710b94156a.pdf','396a0fadeb4cd40f5c8ccc36b73a0815f6cb4d7f6bfa53b6c48c1f9aba7c7e02','E:/xampp/htdocs/my work/Atom/storage/knowledge/originals/1786220190_a5ce84c9c2710b94156a.pdf','## Summary\nThe document (arXiv:2312.10997ΓÇ»v5, submitted 27ΓÇ»MarΓÇ»2024, cs.CL) surveys the stateΓÇæofΓÇætheΓÇæart in RetrievalΓÇæAugmented Generation (RAG). It maps the evolution of RAG across preΓÇætraining, fineΓÇætuning, and inference, describes the canonical RAG pipeline, enumerates a variety of recent RAG methods, and provides extensive guidance on benchmarking and evaluating RAG systems. The paper also discusses ecosystem growth, tooling, and future directions for metrics and specialized applications.\n\n## Key Topics\n- RAG technology tree (preΓÇætraining ΓåÆ fineΓÇætuning ΓåÆ inference)  \n- Core RAG pipeline: indexing, vector encoding, retrieval, generation  \n- Iterative selfΓÇæenhancement and dynamic routing across heterogeneous data sources  \n- Catalog of RAG methods (CoG, Phrase PreΓÇætraining, Iterative DenseX, EAR, etc.)  \n- Benchmark tasks and datasets for RAG evaluation (QA, dialog, extraction, reasoning, etc.)  \n- Evaluation dimensions and corresponding metrics (accuracy, recall, BLEU, ROUGE, etc.)  \n- Comparison of evaluation frameworks (RGB, RECALL, RAGAS, ARES, TruLens, CRUD)  \n- Early learning curves and specialization of the ΓÇ£RAΓÇ»GΓÇ¥ ecosystem  \n- Expansion of the RAG ecosystem and the need for refined metrics  \n- Tooling references (e.g., LangChain recursive text splitter) and related literature on personalized, knowledgeΓÇægrounded dialogue.\n\n## Important Facts\n- **Paper identifier:** arXiv:2312.10997ΓÇ»v5, cs.CL, 27ΓÇ»MarΓÇ»2024.  \n- **Technology tree stages:**  \n  1. **PreΓÇætraining** ΓÇô embedding retrievalΓÇæaware abilities from the ground up.  \n  2. **FineΓÇætuning** ΓÇô adapting LLMs to better integrate retrieved context.  \n  3. **Inference** ΓÇô exploiting external retrieval during generation.  \n- **RAG pipeline steps (Fig.ΓÇ»2):**  \n  1. **Indexing** ΓÇô chunk documents, embed chunks, store vectors in a vectorΓÇæsearch DB.  \n  2. **Retrieval** ΓÇô similarity search for queryΓÇærelevant chunks.  \n  3. **Generation** ΓÇô feed retrieved context to LLM for answer synthesis.  \n- **Iterative selfΓÇæenhancement** (citingΓÇ»[18]): repeatedly refines outputs to improve alignment with source data.  \n- **Dynamic routing:** selects the most appropriate heterogeneous data source per query.  \n- **Methods listed in TableΓÇ»I:** CoG, Wikipedia Text, Phrase PreΓÇætraining, Iterative DenseX, FactoidWiki Text, Proposition Inference, Once, EAR.  \n- **Benchmark task categories (TableΓÇ»II):**  \n  - Question Answering (singleΓÇæhop, multiΓÇæhop, longΓÇæform, domainΓÇæspecific, multiΓÇæchoice, graph QA)  \n  - Dialog (openΓÇædomain, personal, taskΓÇæoriented, recommendation)  \n  - Information Extraction (event argument, relation extraction)  \n  - Reasoning (commonsense, chainΓÇæofΓÇæthought, complex)  \n  - Other NLP (MMLU, language modeling, factΓÇæchecking, summarization, classification, sentiment, code search)  \n- **Evaluation dimensions (TableΓÇ»III) and metric coverage:**  \n  - Dimensions: context relevance, faithfulness, answer relevance, noise robustness, negative rejection, information integration, counterfactual robustness.  \n  - Metrics applicable to all dimensions: Accuracy/Exact Match, Recall, Precision, RΓÇæRate, Cosine similarity, Hit Rate, MRR, NDCG.  \n  - BLEU & ROUGE/ROUGEΓÇæL only for faithfulness & answer relevance.  \n- **Framework comparison (TableΓÇ»IV):** Supports recall, generation quality, counterfactual robustness, RΓÇæRate, plus specialized metrics (RAGAS, ARES, TruLens, CRUD); distinguishes benchmarks (ΓÉû) from tools (ΓÉù) and marks customizations with *.  \n- **Learning curve (Fig.ΓÇ»6):** Shows early performance gains; ΓÇ£SpecializationΓÇ¥ phase fineΓÇætunes RAΓÇ»G for target domains.  \n- **Ecosystem note:** Rapid proliferation of RAG applications and tools; current evaluation practices lag behind, prompting calls for richer metrics.  \n- **Tool reference:** LangChain ΓÇ£Recursive character text splitterΓÇ¥ for document chunking.  \n- **Relevant literature:** Works on personalized knowledgeΓÇægrounded dialogue, longΓÇæterm persona memory, conditional generation, fashion evolution modeling, event argument extraction, multiΓÇæsentence argument linking, largeΓÇæscale NLΓÇæKB alignment.\n\n## How To Use\nWhen responding to user queries about RetrievalΓÇæAugmented Generation, treat this document as a comprehensive reference covering:\n\n1. **Conceptual foundations** ΓÇô describe the threeΓÇæstage RAG technology tree and the standard pipeline (index ΓåÆ retrieve ΓåÆ generate).  \n2. **Methodological landscape** ΓÇô cite specific RAG approaches (e.g., Phrase PreΓÇætraining, Iterative DenseX, EAR) and explain their distinguishing features.  \n3. **Evaluation guidance** ΓÇô map any askedΓÇæabout quality aspect (faithfulness, relevance, robustness, etc.) to the appropriate metric(s) from TableΓÇ»III and indicate which frameworks support them per TableΓÇ»IV.  \n4. **Benchmark context** ΓÇô reference the extensive list of tasks and datasets (TableΓÇ»II) to illustrate how RAG systems are typically tested.  \n5. **Practical tooling** ΓÇô mention common preprocessing steps such as recursive text splitting (LangChain) and the importance of dynamic routing and iterative selfΓÇæenhancement for improving output alignment.  \n6. **Future considerations** ΓÇô note the paperΓÇÖs call for more refined metrics as the RAG ecosystem expands.\n\nLeverage these points to give precise, evidenceΓÇæbacked answers, and when the user asks for comparisons, cite the relevant figures (e.g., Fig.ΓÇ»3, Fig.ΓÇ»4, Fig.ΓÇ»5) or tables to support the explanation.','2026-08-09 02:36:20','2026-08-08 20:16:31');
/*!40000 ALTER TABLE `atom_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_errors`
--

DROP TABLE IF EXISTS `atom_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_id` varchar(50) NOT NULL,
  `category` varchar(100) NOT NULL,
  `severity` varchar(50) DEFAULT 'ERROR',
  `message` text NOT NULL,
  `request_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `error_id` (`error_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_errors`
--

LOCK TABLES `atom_errors` WRITE;
/*!40000 ALTER TABLE `atom_errors` DISABLE KEYS */;
INSERT INTO `atom_errors` VALUES (1,'ERR-A708DAA4','GEMINI_API','ERROR','Local Model Error: Failed to connect to localhost port 11434 after 2251 ms: Couldn\'t connect to server','REQ-B750BAD364F4','2026-08-08 17:49:11');
/*!40000 ALTER TABLE `atom_errors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_feedback`
--

DROP TABLE IF EXISTS `atom_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `feedback_type` varchar(50) NOT NULL,
  `feedback_text` text DEFAULT NULL,
  `associated_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `atom_feedback_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `atom_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_feedback`
--

LOCK TABLES `atom_feedback` WRITE;
/*!40000 ALTER TABLE `atom_feedback` DISABLE KEYS */;
INSERT INTO `atom_feedback` VALUES (1,31,'natural','use shorter answers',NULL,'2026-08-03 19:40:24'),(2,33,'good','/good',NULL,'2026-08-03 19:41:02'),(3,34,'correct','Keep answers extremely short under 3 sentences.',NULL,'2026-08-03 19:41:14');
/*!40000 ALTER TABLE `atom_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_learning_history`
--

DROP TABLE IF EXISTS `atom_learning_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_learning_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic` varchar(255) NOT NULL,
  `action_text` text NOT NULL,
  `source` varchar(100) NOT NULL,
  `confidence` varchar(50) DEFAULT 'MODERATE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_learning_history`
--

LOCK TABLES `atom_learning_history` WRITE;
/*!40000 ALTER TABLE `atom_learning_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `atom_learning_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_learning_progress`
--

DROP TABLE IF EXISTS `atom_learning_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_learning_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `section` varchar(255) DEFAULT NULL,
  `page_start` int(11) NOT NULL,
  `page_end` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'NEW',
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `atom_learning_progress_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `atom_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_learning_progress`
--

LOCK TABLES `atom_learning_progress` WRITE;
/*!40000 ALTER TABLE `atom_learning_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `atom_learning_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_learning_topics`
--

DROP TABLE IF EXISTS `atom_learning_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_learning_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `level` varchar(50) DEFAULT 'beginner',
  `score` int(11) DEFAULT 0,
  `confidence` varchar(50) DEFAULT 'MODERATE',
  `source_count` int(11) DEFAULT 0,
  `successful_uses` int(11) DEFAULT 0,
  `failed_uses` int(11) DEFAULT 0,
  `gemini_consultations` int(11) DEFAULT 0,
  `last_learned_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `practice_count` int(11) DEFAULT 0,
  `successful_count` int(11) DEFAULT 0,
  `last_practiced` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `topic` (`topic`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_learning_topics`
--

LOCK TABLES `atom_learning_topics` WRITE;
/*!40000 ALTER TABLE `atom_learning_topics` DISABLE KEYS */;
INSERT INTO `atom_learning_topics` VALUES (1,'PHP','Development','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(2,'MySQL','Development','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(3,'CodeIgniter','Development','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(4,'Laravel','Development','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(5,'JavaScript','Development','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(6,'React','Development','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(7,'Node.js','Development','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(8,'Linux','Systems','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(9,'Git','Systems','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(10,'System Design','Core CSE','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(11,'GATE CSE','Core CSE','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(12,'Computer Networks','Core CSE','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(13,'Operating Systems','Core CSE','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(14,'DBMS','Core CSE','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL),(15,'Algorithms','Core CSE','BEGINNER',20,'MODERATE',0,0,0,0,NULL,NULL,'2026-08-06 19:36:47','2026-08-06 19:36:47',0,0,NULL);
/*!40000 ALTER TABLE `atom_learning_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_memories`
--

DROP TABLE IF EXISTS `atom_memories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_memories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `memory_key` varchar(100) NOT NULL,
  `memory_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `atom_memories_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `atom_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_memories`
--

LOCK TABLES `atom_memories` WRITE;
/*!40000 ALTER TABLE `atom_memories` DISABLE KEYS */;
/*!40000 ALTER TABLE `atom_memories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_messages`
--

DROP TABLE IF EXISTS `atom_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `atom_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `atom_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_messages`
--

LOCK TABLES `atom_messages` WRITE;
/*!40000 ALTER TABLE `atom_messages` DISABLE KEYS */;
INSERT INTO `atom_messages` VALUES (1,6,'user','hi','2026-08-03 08:30:31'),(2,6,'assistant','ATOM:\nHi! What are you working on today?','2026-08-03 08:30:31'),(3,11,'user','how do I use transactions in PHP?','2026-08-03 08:35:12'),(4,11,'assistant','ATOM:\nI detected that you asked about: \"how do I use transactions in PHP?\"\n\nNote: The LLM model is offline, but I matched the following reference in my local technical knowledge library:\n\n  [1] Source: test (Page 1)\n      \"This is a technical transaction guide for PHP and MySQL database memory solutions....\"\n\nConfigure a `.env` API key to let the LLM generate a summarized answer from these excerpts.','2026-08-03 08:35:12'),(5,21,'user','hi','2026-08-03 08:46:11'),(6,21,'assistant','ATOM:\nHi! What are you working on today?','2026-08-03 08:46:11'),(7,21,'user','who are you','2026-08-03 08:47:02'),(8,21,'assistant','ATOM:\nI detected that you asked about: \"who are you\"\n\nNote: The LLM model is currently offline/unconfigured. Please create a `.env` file in the root directory to enable AI reasoning. Examples:\n\nLLM_API_KEY=your-api-key-here\nLLM_API_URL=https://api.openai.com/v1\n\nMeanwhile, you can use local slash commands like `/help`, `/status`, or `/search`.','2026-08-03 08:47:02'),(9,21,'user','who are you','2026-08-03 08:52:28'),(10,21,'assistant','ATOM:\nI detected that you asked about: \"who are you\"\n\nNote: The LLM model is currently offline/unconfigured. Please create a `.env` file in the root directory to enable AI reasoning. Examples:\n\nLLM_API_KEY=your-api-key-here\nLLM_API_URL=https://api.openai.com/v1\n\nMeanwhile, you can use local slash commands like `/help`, `/status`, or `/search`.','2026-08-03 08:52:28'),(11,26,'user','hi','2026-08-03 08:57:02'),(12,26,'assistant','Hello! I am ATOM, your local PHP Development Assistant. How can I help you today with your project (`E:/xampp/htdocs/my work/Atom`)? Whether you need help with PHP code, MySQL database queries, JavaScript, Bootstrap, HTML/CSS, or analyzing existing code in your workspace, feel free to ask!','2026-08-03 08:57:02'),(13,26,'user','who are you ?','2026-08-03 08:57:37'),(14,26,'assistant','I am **ATOM**, your local-first Personal AI PHP Development Assistant.\n\nMy primary expertise covers:\n- **Core PHP** & **PHP 8.2+** best practices\n- **MySQL** (database design, query optimization, prepared statements)\n- **Frontend Development** (HTML5, CSS3, JavaScript, Bootstrap)\n- **Business Applications** (CRM, ERP, and Billing Systems)\n\nI run directly within your CLI environment and have direct access to analyze, inspect, and update files in your active workspace (`E:/xampp/htdocs/my work/Atom`).\n\nHow can I assist you with your project today?','2026-08-03 08:57:37'),(15,26,'user','Vanakkam','2026-08-03 08:58:45'),(16,26,'assistant','Vanakkam! ≡ƒÖÅ Welcome! How can I assist you today with your PHP development or project work?','2026-08-03 08:58:45'),(17,26,'user','dei','2026-08-03 08:59:29'),(18,26,'assistant','Vanakkam! Tell me, how can I help you with your code or project today?','2026-08-03 08:59:29'),(19,26,'user','what is react?','2026-08-03 09:00:26'),(20,26,'assistant','**React** (also known as React.js or ReactJS) is an open-source JavaScript library developed by Meta (Facebook) for building user interfaces (UIs), specifically single-page applications (SPAs).\n\n### Key Concepts & Features:\n\n1. **Component-Based Architecture**:\n   - The UI is broken down into reusable, self-contained pieces called **components** (e.g., `<Navbar />`, `<Sidebar />`, `<Button />`).\n   - Each component manages its own state and render logic.\n\n2. **Declarative UI**:\n   - You describe what the UI should look like for any given state, and React handles updating and rendering the right components when data changes.\n\n3. **Virtual DOM**:\n   - React maintains an in-memory representation of the real DOM called the **Virtual DOM**.\n   - When state changes, React compares the Virtual DOM with a snapshot of the previous state (a process called *reconciliation* or *diffing*) and updates **only** the parts of the real DOM that actually changed. This makes rendering fast and efficient.\n\n4. **JSX (JavaScript XML)**:\n   - React uses a syntax extension called JSX that allows you to write HTML-like structures directly inside JavaScript code.\n\n   ```jsx\n   function Greeting({ name }) {\n       return <h1>Hello, {name}!</h1>;\n   }\n   ```\n\n5. **Unidirectional Data Flow**:\n   - Data flows down from parent components to child components via `props`.\n\n---\n\n### React + PHP Ecosystem\n\nIn PHP development (like Core PHP, Laravel, or custom ERP/CRM setups), React is commonly used as:\n- **Frontend SPA**: Communicating with a PHP backend via REST or GraphQL APIs.\n- **Embedded Components**: Widget-based integration inside traditional server-rendered PHP templates (e.g., interactive dashboard widgets or billing calculators).','2026-08-03 09:00:26'),(21,26,'user','what is usesstate?','2026-08-03 09:10:55'),(22,26,'assistant','`useState` is a fundamental **React Hook** that allows functional components to store, track, and update their own internal state (data that changes over time).\n\nWhen the state updated using `useState`, React automatically **re-renders** the component to reflect the new data in the UI.\n\n---\n\n### Basic Syntax\n\n```javascript\nimport React, { useState } from \'react\';\n\nconst [state, setState] = useState(initialValue);\n```\n\n1. **`state`**: The current value of the state variable.\n2. **`setState`**: A function used to update the state variable.\n3. **`initialValue`**: The starting value (can be a number, string, boolean, array, object, etc.).\n\n---\n\n### Simple Example: Counter Component\n\n```jsx\nimport React, { useState } from \'react\';\n\nfunction Counter() {\n    // Declare a state variable named \'count\', initialized to 0\n    const [count, setCount] = useState(0);\n\n    return (\n        <div>\n            <p>Current Count: {count}</p>\n            {/* Update state when clicked */}\n            <button onClick={() => setCount(count + 1)}>Increment</button>\n            <button onClick={() => setCount(count - 1)}>Decrement</button>\n        </div>\n    );\n}\n\nexport default Counter;\n```\n\n---\n\n### Working with Objects or Arrays\n\nState updates in React **replace** the old state rather than merging it. When working with objects or arrays, use the spread operator (`...`) to preserve existing values.\n\n#### Updating an Object:\n```jsx\nconst [user, setUser] = useState({ name: \'John\', age: 25 });\n\n// Correct way to update only \'age\':\nsetUser(prevUser => ({\n    ...prevUser,\n    age: 26\n}));\n```\n\n---\n\n### Key Points to Remember\n\n- **Triggers Re-render**: Calling the update function (`setState`) schedules a re-render of the component with the new state.\n- **Asynchronous/Batched**: React batches state updates for performance, so state updates are not applied immediately in the same line of code.\n- **Hook Rule**: `useState` can only be called at the **top level** of a functional component (not inside loops, conditions, or nested functions).','2026-08-03 09:10:55'),(23,27,'user','hi','2026-08-03 09:51:35'),(24,27,'assistant','Hello! I am **ATOM**, your local PHP Development Assistant.\n\nHow can I help you today? Whether you need help with Core PHP, MySQL queries, front-end design with Bootstrap/CSS/JS, or structuring features for your CRM, ERP, or billing system, let me know!','2026-08-03 09:51:35'),(25,27,'user','vanakkam','2026-08-03 09:51:48'),(26,27,'assistant','Vanakkam! ≡ƒÖÅ \n\nHow can I assist you with your PHP, MySQL, or web development tasks today?','2026-08-03 09:51:48'),(27,27,'user','KTG karur','2026-08-03 09:52:26'),(28,27,'assistant','I couldn\'t find any references to \"KTG\" or \"Karur\" in your workspace. \n\nCould you please clarify what \"KTG Karur\" refers to? Are you looking to set up or develop a specific project, database structure, or module (like an ERP, billing system, or CRM) for a Karur-based business/client? Let me know how I can help!','2026-08-03 09:52:26'),(29,29,'user','hi','2026-08-03 19:39:34'),(30,29,'assistant','Hello! I\'m **ATOM**, your local-first Personal AI PHP Development Assistant.\n\nI\'m ready to help you with your project in `E:/xampp/htdocs/my work/Atom`. My expertise includes:\n- **Core PHP** (8.2+)\n- **MySQL** & Database Design (Prepared Statements, Indexing, Schema Optimization)\n- **Frontend Development** (HTML/CSS, JavaScript, Bootstrap)\n- **Business Applications** (CRM, ERP, Billing, Invoicing Systems)\n- **Code Quality & Security** (PHP Linting, Input Validation, Output Escaping)\n\nHow can I assist you today? Feel free to ask a question or request a codebase inspection/file modification.','2026-08-03 19:39:34'),(31,30,'user','use shorter answers','2026-08-03 19:39:47'),(32,30,'assistant','Understood. I will keep responses concise and to the point.','2026-08-03 19:39:47'),(33,32,'user','explain PHP session','2026-08-03 19:40:43'),(34,32,'assistant','### What is a PHP Session?\n\nA PHP session allows you to store user data across multiple HTTP requests on the server. Because HTTP is stateless, sessions maintain user state (like login status or shopping cart items) between page reloads.\n\n---\n\n### How It Works\n1. **Session Start**: PHP checks for a session cookie named `PHPSESSID` sent by the client browser.\n2. **Session ID**: If found, PHP restores the session data. If not, PHP creates a new unique Session ID and sends it to the browser as a cookie.\n3. **Data Storage**: Session variables are stored on the server side (by default in temporary files).\n\n---\n\n### Usage Example\n\n#### 1. Starting & Setting Session Variables\n```php\n<?php\n// Must be called before any HTML output\nsession_start();\n\n$_SESSION[\'user_id\'] = 42;\n$_SESSION[\'username\'] = \'john_doe\';\n```\n\n#### 2. Reading Session Variables\n```php\n<?php\nsession_start();\n\nif (isset($_SESSION[\'user_id\'])) {\n    echo \"Welcome, \" . htmlspecialchars($_SESSION[\'username\'], ENT_QUOTES, \'UTF-8\');\n}\n```\n\n#### 3. Destroying a Session (Logout)\n```php\n<?php\nsession_start();\n\n// Unset all session variables\n$_SESSION = [];\n\n// Delete the session cookie\nif (ini_get(\"session.use_cookies\")) {\n    $params = session_get_cookie_params();\n    setcookie(\n        session_name(), \'\', time() - 42000,\n        $params[\"path\"], $params[\"domain\"],\n        $params[\"secure\"], $params[\"httponly\"]\n    );\n}\n\n// Destroy the session\nsession_destroy();\n```\n\n---\n\n### Security Best Practices\n- **Prevent Session Fixation**: Call `session_regenerate_id(true)` upon privilege changes (e.g., successful login).\n- **Secure Cookie Flags**: Configure `session.cookie_httponly = 1`, `session.cookie_secure = 1` (HTTPS), and `session.cookie_samesite = \'Lax\'` in `php.ini` or via `session_set_cookie_params()`.','2026-08-03 19:40:44'),(35,36,'user','hi','2026-08-03 19:53:16'),(36,36,'assistant','Hello! I\'m ATOM, your local PHP development assistant. How can I help you with your project today?','2026-08-03 19:53:16'),(37,37,'user','Say hello briefly.','2026-08-03 19:56:51'),(38,37,'assistant','Hello! I\'m ATOM, your local PHP development assistant. How can I help you today?','2026-08-03 19:56:51'),(39,38,'user','hi','2026-08-03 19:57:57'),(40,38,'assistant','Hello! I\'m ATOM, your local PHP development assistant. How can I help you with your project today?','2026-08-03 19:57:57'),(41,42,'user','hi','2026-08-03 20:08:31'),(42,42,'assistant','Hello Vishnupriyan! How can I assist you with your PHP and web development projects today?','2026-08-03 20:08:31'),(43,44,'user','hi','2026-08-06 19:04:06'),(44,44,'assistant','Hello Vishnupriyan! How can I help you with your PHP development today?','2026-08-06 19:04:06'),(45,44,'user','exit','2026-08-06 19:16:46'),(46,44,'assistant','Goodbye Vichu! Let me know whenever you need assistance with your code again.','2026-08-06 19:16:46'),(47,44,'user','exit','2026-08-06 19:16:58'),(48,44,'assistant','Goodbye! Feel free to reach out whenever you are ready to work on your projects.','2026-08-06 19:16:58'),(49,44,'user','back','2026-08-06 19:17:35'),(50,44,'assistant','Welcome back Vichu! What project or task are we working on now?','2026-08-06 19:17:35'),(51,45,'user','who i am','2026-08-06 19:31:03'),(52,45,'assistant','You are Vichu, a PHP Full-Stack Developer with a B.E. in Computer Science and Engineering from VSB Engineering College (2020ΓÇô2024). You specialize in building web applications, CRMs, ERPs, and billing systems, with ambitions targeting GATE 2028 and research roles at DRDO/ISRO/BARC.','2026-08-06 19:31:03'),(53,45,'user','who are you','2026-08-06 19:31:54'),(54,45,'assistant','I am ATOM, your local-first Personal AI PHP Development Assistant. I\'m here to help you code, inspect, and optimize your PHP applications and web projects directly in your workspace.','2026-08-06 19:31:54'),(55,45,'user','leraning','2026-08-06 19:41:14'),(56,45,'assistant','Your listed learning goals include **Advanced PHP, Laravel, React, React Native CLI, Angular, Node.js, Express.js, .NET, DevOps, and AI development**. Which of these topics would you like to explore or practice today?','2026-08-06 19:41:14'),(57,58,'user','hi who i am','2026-08-07 06:20:41'),(58,58,'assistant','You are Vishnupriyan R (Vichu), a PHP Full-Stack Developer from Karur, Tamil Nadu. You graduated with a B.E. in Computer Science and Engineering from VSB Engineering College and specialize in building web applications, CRMs, and billing systems.','2026-08-07 06:20:41'),(59,59,'user','hi','2026-08-07 06:21:09'),(60,59,'assistant','Hello Vichu! How can I assist you with your PHP development or project today?','2026-08-07 06:21:09'),(61,60,'user','hi','2026-08-07 09:38:45'),(62,60,'assistant','Hello Vichu! How can I help you with your PHP development or project tasks today?','2026-08-07 09:38:45'),(63,61,'user','hi','2026-08-07 09:40:41'),(64,61,'assistant','Hello Vichu! How can I assist you with your PHP development or project today?','2026-08-07 09:40:42'),(65,62,'user','hi','2026-08-07 09:41:05'),(66,62,'assistant','Hi Vichu! How can I help you with your PHP or full-stack development projects today?','2026-08-07 09:41:06'),(67,62,'user','who i am','2026-08-07 09:41:19'),(68,62,'assistant','You are Vishnupriyan R (Vichu), a PHP Full-Stack Developer from Karur, Tamil Nadu. You hold a B.E. in Computer Science and Engineering from VSB Engineering College.','2026-08-07 09:41:19'),(69,63,'user','hi','2026-08-08 17:51:13'),(70,63,'assistant','Hello Vichu! How can I help you with your PHP or full-stack projects today?','2026-08-08 17:51:13'),(71,65,'user','What is the capital of France?','2026-08-08 17:55:31'),(72,65,'assistant','The capital of France is Paris.','2026-08-08 17:55:32'),(73,66,'user','Say hello in one word','2026-08-08 17:55:49'),(74,66,'assistant','Hello!','2026-08-08 17:55:49'),(75,73,'user','hi','2026-08-08 18:42:12'),(76,73,'assistant','Hi Vichu! How can I assist you with your PHP or fullΓÇæstack development today?','2026-08-08 18:42:12'),(77,73,'user','hi','2026-08-08 18:55:13'),(78,73,'assistant','Hello Vichu! What would you like help with today?','2026-08-08 18:55:13'),(79,74,'user','Reply with exactly: GROQ OK','2026-08-08 20:20:08'),(80,74,'assistant','GROQ OK','2026-08-08 20:20:08'),(81,75,'user','Reply with exactly: GEMINI OK','2026-08-08 20:20:27'),(82,75,'assistant','GEMINI OK','2026-08-08 20:20:27');
/*!40000 ALTER TABLE `atom_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_owner_profile`
--

DROP TABLE IF EXISTS `atom_owner_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_owner_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL DEFAULT 'Vishnupriyan R',
  `preferred_name` varchar(255) DEFAULT NULL,
  `atom_display_name` varchar(255) DEFAULT 'ATOM',
  `profile_image` varchar(500) DEFAULT NULL,
  `preferred_language` varchar(100) DEFAULT 'English',
  `response_style` varchar(100) DEFAULT 'concise',
  `explanation_level` varchar(100) DEFAULT 'intermediate',
  `main_technologies` text DEFAULT NULL,
  `main_use_cases` text DEFAULT NULL,
  `timezone` varchar(100) DEFAULT 'Asia/Kolkata',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_owner_profile`
--

LOCK TABLES `atom_owner_profile` WRITE;
/*!40000 ALTER TABLE `atom_owner_profile` DISABLE KEYS */;
INSERT INTO `atom_owner_profile` VALUES (1,'Vishnupriyan R','Vichu','ATOM','','English','detailed','practical, project-oriented','PHP, CodeIgniter, Laravel, MySQL, JavaScript, jQuery, CSS, Bootstrap, React, React Native CLI, Angular, Node.js, Express.js','Software development, GATE 2028 preparation, Study guidance, Personal assistant','Asia/Kolkata','2026-08-03 20:08:15','2026-08-06 19:14:07');
/*!40000 ALTER TABLE `atom_owner_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_personal_profile`
--

DROP TABLE IF EXISTS `atom_personal_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_personal_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text NOT NULL,
  `source` varchar(100) NOT NULL,
  `confidence` float DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_pref` (`project_id`,`preference_key`),
  CONSTRAINT `atom_personal_profile_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `atom_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_personal_profile`
--

LOCK TABLES `atom_personal_profile` WRITE;
/*!40000 ALTER TABLE `atom_personal_profile` DISABLE KEYS */;
INSERT INTO `atom_personal_profile` VALUES (1,1,'preferred_response_length','short','user_correction',1,'2026-08-03 19:40:24','2026-08-03 19:40:24'),(2,1,'last_correction','Keep answers extremely short under 3 sentences.','user_correction',1,'2026-08-03 19:41:14','2026-08-03 19:41:14'),(3,NULL,'education','BE in Computer Science and Engineering from VSB Engineering College, Karur (2020ΓÇô2024)','explicit_user_request',1,'2026-08-06 19:14:07','2026-08-06 19:14:07'),(4,NULL,'os_environment','Windows 11 Home Single Language, WSL 2, Ubuntu','explicit_user_request',1,'2026-08-06 19:14:07','2026-08-06 19:14:07'),(5,NULL,'primary_role','PHP Developer / PHP Full-Stack Developer','explicit_user_request',1,'2026-08-06 19:14:08','2026-08-06 19:14:08'),(6,NULL,'learning_goals','Advanced PHP, Laravel, React, React Native CLI, Angular, Node.js, Express.js, .NET, DevOps, AI development','explicit_user_request',1,'2026-08-06 19:14:08','2026-08-06 19:14:08'),(7,NULL,'career_goals','GATE 2028 preparation, DRDO/ISRO/BARC opportunities, Freelancing, Website/Server maintenance contracts','explicit_user_request',1,'2026-08-06 19:14:08','2026-08-06 19:14:08'),(8,NULL,'interests','ARK: Survival Evolved (The Island map), Travel, Online gaming, Sour & spicy food, curd without salt','explicit_user_request',1,'2026-08-06 19:14:08','2026-08-06 19:14:08'),(9,NULL,'favorite_colors','Black, Red, Green, White','explicit_user_request',1,'2026-08-06 19:14:08','2026-08-06 19:14:08'),(10,NULL,'explanation_preference','Problem -> Explanation -> Solution -> Code -> Testing -> Improvements','explicit_user_request',1,'2026-08-06 19:14:08','2026-08-06 19:14:08'),(11,NULL,'teaching_style','practical, project-oriented approach','explicit_user_request',1,'2026-08-06 19:14:08','2026-08-06 19:14:08'),(12,NULL,'business_software_experience','CRM, Project management, Attendance, Payroll, Invoicing, Billing, Daily task reports, cron automation','explicit_user_request',1,'2026-08-06 19:14:08','2026-08-06 19:14:08');
/*!40000 ALTER TABLE `atom_personal_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_projects`
--

DROP TABLE IF EXISTS `atom_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `path` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_projects`
--

LOCK TABLES `atom_projects` WRITE;
/*!40000 ALTER TABLE `atom_projects` DISABLE KEYS */;
INSERT INTO `atom_projects` VALUES (1,'Atom','E:/xampp/htdocs/my work/Atom','2026-08-03 07:51:38');
/*!40000 ALTER TABLE `atom_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_requests`
--

DROP TABLE IF EXISTS `atom_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` varchar(50) NOT NULL,
  `user_query` text NOT NULL,
  `intent` varchar(100) DEFAULT NULL,
  `resolution_type` varchar(100) DEFAULT 'LOCAL',
  `provider` varchar(100) DEFAULT NULL,
  `rag_used` tinyint(4) DEFAULT 0,
  `confidence` int(11) DEFAULT 0,
  `duration_ms` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'SUCCESS',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id` (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_requests`
--

LOCK TABLES `atom_requests` WRITE;
/*!40000 ALTER TABLE `atom_requests` DISABLE KEYS */;
INSERT INTO `atom_requests` VALUES (1,'REQ-096687DF38B1','hi','conversation','COLLABORATIVE','Gemini',0,20,3735,'SUCCESS','2026-08-07 09:40:43'),(2,'REQ-6715210513EF','hi','conversation','COLLABORATIVE','Gemini',0,20,3869,'SUCCESS','2026-08-07 09:41:06'),(3,'REQ-DB67811A4E7A','who i am','general','COLLABORATIVE','Gemini',0,20,2614,'SUCCESS','2026-08-07 09:41:20'),(4,'REQ-B750BAD364F4','What is the capital of France?','general','COLLABORATIVE','gemini',0,20,4124,'FAILED','2026-08-08 17:49:11'),(5,'REQ-1B5F92FA6349','hi','conversation','COLLABORATIVE','Gemini',0,20,3592,'SUCCESS','2026-08-08 17:51:13'),(6,'REQ-012563B48EA1','What is the capital of France?','general','COLLABORATIVE','gemini',0,20,4096,'SUCCESS','2026-08-08 17:55:32'),(7,'REQ-531425BE3BCE','Say hello in one word','general','COLLABORATIVE','gemini',0,20,7509,'SUCCESS','2026-08-08 17:55:49'),(8,'REQ-4B398C17869A','hi','conversation','COLLABORATIVE','groq',0,20,1856,'SUCCESS','2026-08-08 18:42:12'),(9,'REQ-77A8558EC8CF','hi','conversation','COLLABORATIVE','groq',0,20,1475,'SUCCESS','2026-08-08 18:55:13'),(10,'REQ-C5E69D33B653','Reply with exactly: GROQ OK','general','COLLABORATIVE','Groq',1,60,2795,'SUCCESS','2026-08-08 20:20:08'),(11,'REQ-12391AAD86F7','Reply with exactly: GEMINI OK','general','COLLABORATIVE','Gemini',1,60,10862,'SUCCESS','2026-08-08 20:20:27');
/*!40000 ALTER TABLE `atom_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_responses`
--

DROP TABLE IF EXISTS `atom_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` varchar(50) NOT NULL,
  `conversation_id` int(11) DEFAULT 0,
  `final_response` text NOT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_req` (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_responses`
--

LOCK TABLES `atom_responses` WRITE;
/*!40000 ALTER TABLE `atom_responses` DISABLE KEYS */;
INSERT INTO `atom_responses` VALUES (1,'REQ-096687DF38B1',0,'Hello Vichu! How can I assist you with your PHP development or project today?','Gemini','gemini-flash-latest','2026-08-07 09:40:43'),(2,'REQ-6715210513EF',0,'Hi Vichu! How can I help you with your PHP or full-stack development projects today?','Gemini','gemini-flash-latest','2026-08-07 09:41:07'),(3,'REQ-DB67811A4E7A',0,'You are Vishnupriyan R (Vichu), a PHP Full-Stack Developer from Karur, Tamil Nadu. You hold a B.E. in Computer Science and Engineering from VSB Engineering College.','Gemini','gemini-flash-latest','2026-08-07 09:41:20'),(4,'REQ-1B5F92FA6349',0,'Hello Vichu! How can I help you with your PHP or full-stack projects today?','Gemini','gemini-flash-latest','2026-08-08 17:51:13'),(5,'REQ-012563B48EA1',0,'The capital of France is Paris.','gemini','gemini-3.6-flash','2026-08-08 17:55:32'),(6,'REQ-531425BE3BCE',0,'Hello!','gemini','gemini-3.6-flash','2026-08-08 17:55:49'),(7,'REQ-4B398C17869A',0,'Hi Vichu! How can I assist you with your PHP or fullΓÇæstack development today?','groq','openai/gpt-oss-120b','2026-08-08 18:42:12'),(8,'REQ-77A8558EC8CF',0,'Hello Vichu! What would you like help with today?','groq','openai/gpt-oss-120b','2026-08-08 18:55:13'),(9,'REQ-C5E69D33B653',0,'GROQ OK','Groq','openai/gpt-oss-120b','2026-08-08 20:20:09'),(10,'REQ-12391AAD86F7',0,'GEMINI OK','Gemini','gemini-3.6-flash','2026-08-08 20:20:27');
/*!40000 ALTER TABLE `atom_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_sessions`
--

DROP TABLE IF EXISTS `atom_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `session_uuid` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_uuid` (`session_uuid`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `atom_sessions_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `atom_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_sessions`
--

LOCK TABLES `atom_sessions` WRITE;
/*!40000 ALTER TABLE `atom_sessions` DISABLE KEYS */;
INSERT INTO `atom_sessions` VALUES (1,1,'atom_session_6a70488aca9ae4.44520158','2026-08-03 07:51:38'),(2,1,'atom_session_6a704896001582.47639101','2026-08-03 07:51:50'),(3,1,'atom_session_6a704958569384.29866502','2026-08-03 07:55:04'),(4,1,'atom_session_6a70495f9095e0.75551836','2026-08-03 07:55:11'),(5,1,'atom_session_6a7049675fb540.49145501','2026-08-03 07:55:19'),(6,1,'atom_session_6a7051a7155a97.52634641','2026-08-03 08:30:31'),(7,1,'atom_session_6a7051b63681d0.71957735','2026-08-03 08:30:46'),(8,1,'atom_session_6a7051d70b46f7.59910742','2026-08-03 08:31:19'),(9,1,'atom_session_6a7052b2a3f870.46102967','2026-08-03 08:34:58'),(10,1,'atom_session_6a7052b98b94b8.66224892','2026-08-03 08:35:05'),(11,1,'atom_session_6a7052c00cdcd0.01666065','2026-08-03 08:35:12'),(12,1,'atom_session_6a7053263585f1.43736720','2026-08-03 08:36:54'),(13,1,'atom_session_6a70532d90a0a3.12581692','2026-08-03 08:37:01'),(14,1,'atom_session_6a7053341ed0d7.74533823','2026-08-03 08:37:08'),(15,1,'atom_session_6a70533c264c13.45753355','2026-08-03 08:37:16'),(16,1,'atom_session_6a7053436b29b1.58173685','2026-08-03 08:37:23'),(17,1,'atom_session_6a70534a00c177.61109614','2026-08-03 08:37:30'),(18,1,'atom_session_6a705350e4de38.50750257','2026-08-03 08:37:36'),(19,1,'atom_session_6a7053c6142e64.42735996','2026-08-03 08:39:34'),(20,1,'atom_session_6a7054a92e29e6.02366081','2026-08-03 08:43:21'),(21,1,'atom_session_6a70554d2a1751.13294984','2026-08-03 08:46:05'),(22,1,'atom_session_6a70572677d224.94183547','2026-08-03 08:53:58'),(23,1,'atom_session_6a70573c7e1be6.25600500','2026-08-03 08:54:20'),(24,1,'atom_session_6a7057513119e6.92001980','2026-08-03 08:54:41'),(25,1,'atom_session_6a705768098af4.38421710','2026-08-03 08:55:04'),(26,1,'atom_session_6a7057d6255411.46416712','2026-08-03 08:56:54'),(27,1,'atom_session_6a70649f093376.56244825','2026-08-03 09:51:27'),(28,1,'atom_session_6a70ee674709b6.81139582','2026-08-03 19:39:19'),(29,1,'atom_session_6a70ee722ddc80.97056231','2026-08-03 19:39:30'),(30,1,'atom_session_6a70ee8033bee2.28779557','2026-08-03 19:39:44'),(31,1,'atom_session_6a70eea7c96427.96942303','2026-08-03 19:40:23'),(32,1,'atom_session_6a70eeb5555116.73906666','2026-08-03 19:40:37'),(33,1,'atom_session_6a70eece9a1202.74009377','2026-08-03 19:41:02'),(34,1,'atom_session_6a70eedaa40119.55656543','2026-08-03 19:41:14'),(35,1,'atom_session_6a70f115a366e8.80862266','2026-08-03 19:50:45'),(36,1,'atom_session_6a70f1a7f1dd45.64242095','2026-08-03 19:53:12'),(37,1,'atom_session_6a70f27f6659d5.87557824','2026-08-03 19:56:47'),(38,1,'atom_session_6a70f2beee3594.42484625','2026-08-03 19:57:50'),(39,1,'atom_session_6a70f474c61942.97841703','2026-08-03 20:05:08'),(40,1,'atom_session_6a70f4f6186bb6.06204052','2026-08-03 20:07:18'),(41,1,'atom_session_6a70f52ea7a975.92817942','2026-08-03 20:08:14'),(42,1,'atom_session_6a70f53b8b7411.51970974','2026-08-03 20:08:27'),(43,1,'atom_session_6a70f548b74e76.94525437','2026-08-03 20:08:40'),(44,1,'atom_session_6a74da9b97eab0.89250894','2026-08-06 19:03:55'),(45,1,'atom_session_6a74ddd59ebdc8.54122864','2026-08-06 19:17:41'),(46,1,'atom_session_6a74e18016b833.76398390','2026-08-06 19:33:20'),(47,1,'atom_session_6a74e1886b11a3.62372319','2026-08-06 19:33:28'),(48,1,'atom_session_6a74e19d56a301.27801769','2026-08-06 19:33:49'),(49,1,'atom_session_6a74e2be222ef6.11609072','2026-08-06 19:38:38'),(50,1,'atom_session_6a74e2d8d6ede3.10802361','2026-08-06 19:39:04'),(51,1,'atom_session_6a74e30f0d0838.70848853','2026-08-06 19:39:59'),(52,1,'atom_session_6a74e317c2c994.69041290','2026-08-06 19:40:07'),(53,1,'atom_session_6a74e32071a141.39054020','2026-08-06 19:40:16'),(54,1,'atom_session_6a74e32e473b27.88013888','2026-08-06 19:40:30'),(55,1,'atom_session_6a74e39bec9b93.34499604','2026-08-06 19:42:19'),(56,1,'atom_session_6a74e62c5f3d42.64805711','2026-08-06 19:53:16'),(57,1,'atom_session_6a74e9e32fb865.71280269','2026-08-06 20:09:07'),(58,1,'atom_session_6a7578ef5cff80.41650489','2026-08-07 06:19:27'),(59,1,'atom_session_6a75794a52c1f6.75087670','2026-08-07 06:20:58'),(60,1,'atom_session_6a75a79b89aa53.74461594','2026-08-07 09:38:35'),(61,1,'atom_session_6a75a816581764.47274860','2026-08-07 09:40:38'),(62,1,'atom_session_6a75a82bb36125.77990913','2026-08-07 09:40:59'),(63,1,'atom_session_6a77638c383f71.08945931','2026-08-08 17:12:44'),(64,1,'atom_session_6a776c129de9a5.18454082','2026-08-08 17:49:06'),(65,1,'atom_session_6a776d8f85cc92.75236458','2026-08-08 17:55:27'),(66,1,'atom_session_6a776d9e13f2e9.09087538','2026-08-08 17:55:42'),(67,1,'atom_session_6a776f0cb9ec44.61477556','2026-08-08 18:01:48'),(68,1,'atom_session_6a77728c1b31b9.03685210','2026-08-08 18:16:44'),(69,1,'atom_session_6a7773bd7cfe54.25587842','2026-08-08 18:21:49'),(70,1,'atom_session_6a777604caa919.16675858','2026-08-08 18:31:32'),(71,1,'atom_session_6a7776ad1fa044.81347409','2026-08-08 18:34:21'),(72,1,'atom_session_6a7777b63a7680.38338456','2026-08-08 18:38:46'),(73,1,'atom_session_6a77783f91d361.15830384','2026-08-08 18:41:03'),(74,1,'atom_session_6a778f759ffa97.87774931','2026-08-08 20:20:05'),(75,1,'atom_session_6a778f80961083.15172757','2026-08-08 20:20:16'),(76,1,'atom_session_6a7799e53b6f88.26736005','2026-08-08 21:04:37');
/*!40000 ALTER TABLE `atom_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_settings`
--

DROP TABLE IF EXISTS `atom_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_settings`
--

LOCK TABLES `atom_settings` WRITE;
/*!40000 ALTER TABLE `atom_settings` DISABLE KEYS */;
INSERT INTO `atom_settings` VALUES (1,'gemini_consultations','14','2026-08-07 06:20:38');
/*!40000 ALTER TABLE `atom_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_solutions`
--

DROP TABLE IF EXISTS `atom_solutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_solutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `problem_summary` varchar(255) NOT NULL,
  `root_cause` text NOT NULL,
  `solution_text` text NOT NULL,
  `technology` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `atom_solutions_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `atom_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_solutions`
--

LOCK TABLES `atom_solutions` WRITE;
/*!40000 ALTER TABLE `atom_solutions` DISABLE KEYS */;
/*!40000 ALTER TABLE `atom_solutions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_tool_executions`
--

DROP TABLE IF EXISTS `atom_tool_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_tool_executions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_name` varchar(100) NOT NULL,
  `arguments` text NOT NULL,
  `result` text NOT NULL,
  `duration_ms` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'SUCCESS',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_tool_executions`
--

LOCK TABLES `atom_tool_executions` WRITE;
/*!40000 ALTER TABLE `atom_tool_executions` DISABLE KEYS */;
/*!40000 ALTER TABLE `atom_tool_executions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atom_training_examples`
--

DROP TABLE IF EXISTS `atom_training_examples`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `atom_training_examples` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) DEFAULT NULL,
  `user_input` text NOT NULL,
  `context_summary` text DEFAULT NULL,
  `preferred_response` text NOT NULL,
  `source` varchar(100) NOT NULL,
  `quality` varchar(50) DEFAULT 'UNREVIEWED',
  `verified` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atom_training_examples`
--

LOCK TABLES `atom_training_examples` WRITE;
/*!40000 ALTER TABLE `atom_training_examples` DISABLE KEYS */;
/*!40000 ALTER TABLE `atom_training_examples` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `is_pinned` tinyint(4) NOT NULL DEFAULT 0,
  `folder_id` int(10) unsigned DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `is_pinned` (`is_pinned`),
  KEY `folder_id` (`folder_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chats`
--

LOCK TABLES `chats` WRITE;
/*!40000 ALTER TABLE `chats` DISABLE KEYS */;
INSERT INTO `chats` VALUES (1,'Test Chat','gpt-4o','OpenAI',0,NULL,NULL,'2026-07-28 07:51:50','2026-07-28 07:51:50'),(2,'New Chat','llama3.1','Ollama',0,NULL,NULL,'2026-07-29 09:55:59','2026-07-29 09:55:59'),(3,'New Chat','llama3.1','Ollama',0,NULL,NULL,'2026-07-29 09:55:59','2026-07-29 09:55:59'),(4,'New Chat','Local Server','LM Studio',0,NULL,NULL,'2026-07-29 10:21:17','2026-07-29 10:21:17'),(5,'New Chat','llama3.1','Ollama',0,NULL,NULL,'2026-08-03 20:05:07','2026-08-03 20:05:07'),(6,'Test','gemini-3.6-flash','Gemini',0,NULL,NULL,'2026-08-08 17:55:41','2026-08-08 17:55:41');
/*!40000 ALTER TABLE `chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_records`
--

DROP TABLE IF EXISTS `file_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `path` varchar(500) NOT NULL,
  `size` int(11) NOT NULL DEFAULT 0,
  `type` varchar(50) DEFAULT NULL,
  `chat_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`),
  CONSTRAINT `file_records_chat_id_foreign` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_records`
--

LOCK TABLES `file_records` WRITE;
/*!40000 ALTER TABLE `file_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `file_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_items`
--

DROP TABLE IF EXISTS `knowledge_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knowledge_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `collection` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collection` (`collection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_items`
--

LOCK TABLES `knowledge_items` WRITE;
/*!40000 ALTER TABLE `knowledge_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `knowledge_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` int(10) unsigned NOT NULL,
  `role` varchar(20) NOT NULL,
  `content` longtext NOT NULL,
  `tokens_in` int(11) DEFAULT NULL,
  `tokens_out` int(11) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`),
  CONSTRAINT `messages_chat_id_foreign` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,2,'user','hi',NULL,NULL,NULL,NULL),(2,2,'assistant','I\'m running in preview mode with Ollama (llama3.1).\n\nTo connect a real AI provider:\n  - **Ollama**: Run `ollama serve` on localhost:11434\n  - **OpenAI**: Set your API key in Settings\n  - **Anthropic**: Set your API key in Settings\n  - **LM Studio**: Start the local server\n\nYour message has been saved to the database. Once a provider is connected, I\'ll respond intelligently.',NULL,NULL,'llama3.1',NULL),(3,3,'user','hi',NULL,NULL,NULL,NULL),(4,3,'assistant','I\'m running in preview mode with Ollama (llama3.1).\n\nTo connect a real AI provider:\n  - **Ollama**: Run `ollama serve` on localhost:11434\n  - **OpenAI**: Set your API key in Settings\n  - **Anthropic**: Set your API key in Settings\n  - **LM Studio**: Start the local server\n\nYour message has been saved to the database. Once a provider is connected, I\'ll respond intelligently.',NULL,NULL,'llama3.1',NULL),(5,2,'user','hi',NULL,NULL,NULL,NULL),(6,2,'assistant','I\'m running in preview mode with Ollama (llama3.1).\n\nTo connect a real AI provider:\n  - **Ollama**: Run `ollama serve` on localhost:11434\n  - **OpenAI**: Set your API key in Settings\n  - **Anthropic**: Set your API key in Settings\n  - **LM Studio**: Start the local server\n\nYour message has been saved to the database. Once a provider is connected, I\'ll respond intelligently.',NULL,NULL,'llama3.1',NULL),(7,4,'user','hi',NULL,NULL,NULL,NULL),(8,4,'assistant','Running in preview mode with LM Studio (Local Server).\n\nTo connect a real AI provider:\n  - **Ollama**: Run `ollama serve` on localhost:11434\n  - **LM Studio**: Start the local server\n  - Add a custom model via `atom.bat` > Add Custom Model\n\nYour message has been saved. Once a provider is connected, I\'ll respond intelligently.',NULL,NULL,'Local Server',NULL),(9,4,'user','hi',NULL,NULL,NULL,NULL),(10,4,'assistant','Running in preview mode with LM Studio (Local Server).\n\nTo connect a real AI provider:\n  - **Ollama**: Run `ollama serve` on localhost:11434\n  - **LM Studio**: Start the local server\n  - Add a custom model via `atom.bat` > Add Custom Model\n\nYour message has been saved. Once a provider is connected, I\'ll respond intelligently.',NULL,NULL,'Local Server',NULL),(11,1,'user','hi',NULL,NULL,NULL,NULL),(12,1,'assistant','Running in preview mode with OpenAI (gpt-4o).\n\nTo connect a real AI provider:\n  - **Ollama**: Run `ollama serve` on localhost:11434\n  - **LM Studio**: Start the local server\n  - Add a custom model via `atom.bat` > Add Custom Model\n\nYour message has been saved. Once a provider is connected, I\'ll respond intelligently.',NULL,NULL,'gpt-4o',NULL),(13,5,'user','hi',NULL,NULL,NULL,NULL),(14,5,'user','hi',NULL,NULL,NULL,NULL),(15,5,'assistant','ATOM Error: Gemini API HTTP Error (429): {\n  \"error\": {\n    \"code\": 429,\n    \"message\": \"You exceeded your current quota, please check your plan and billing details. For more information on this error, head to: https://ai.google.dev/gemini-api/docs/rate-limits. To monitor your current usage, head to: https://ai.dev/rate-limit. \\n* Quota exceeded for metric: generativelanguage.googleapis.com/generate_content_free_tier_requests, limit: 20, model: gemini-3.6-flash\\nPlease retry in 38.340624494s.\",\n    \"status\": \"RESOURCE_EXHAUSTED\",\n    \"details\": [\n      {\n        \"@type\": \"type.googleapis.com/google.rpc.Help\",\n        \"links\": [\n          {\n            \"description\": \"Learn more about Gemini API quotas\",\n            \"url\": \"https://ai.google.dev/gemini-api/docs/rate-limits\"\n          }\n        ]\n      },\n      {\n        \"@type\": \"type.googleapis.com/google.rpc.QuotaFailure\",\n        \"violations\": [\n          {\n            \"quotaMetric\": \"generativelanguage.googleapis.com/generate_content_free_tier_requests\",\n            \"quotaId\": \"GenerateRequestsPerDayPerProjectPerModel-FreeTier\",\n            \"quotaDimensions\": {\n              \"location\": \"global\",\n              \"model\": \"gemini-3.6-flash\"\n            },\n            \"quotaValue\": \"20\"\n          }\n        ]\n      },\n      {\n        \"@type\": \"type.googleapis.com/google.rpc.RetryInfo\",\n        \"retryDelay\": \"38s\"\n      }\n    ]\n  }\n}\n\n(Check network connection or credentials config)',NULL,NULL,'llama3.1',NULL),(16,6,'user','Say hello in one word',NULL,NULL,NULL,NULL),(17,6,'assistant','Hello!',NULL,NULL,'gemini-3.6-flash',NULL);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-07-28-000001','App\\Database\\Migrations\\CreateAtomAssistantTables','default','App',1785224732,1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `folder` varchar(255) DEFAULT NULL,
  `is_favorite` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `folder` (`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugins`
--

DROP TABLE IF EXISTS `plugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon_path` varchar(500) DEFAULT NULL,
  `is_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `installed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugins`
--

LOCK TABLES `plugins` WRITE;
/*!40000 ALTER TABLE `plugins` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prompts`
--

DROP TABLE IF EXISTS `prompts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prompts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_favorite` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prompts`
--

LOCK TABLES `prompts` WRITE;
/*!40000 ALTER TABLE `prompts` DISABLE KEYS */;
/*!40000 ALTER TABLE `prompts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'string',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'test@atom.dev','$2y$10$7JAC0UeHrERpxAzCr.8FdOwvJguUiLAWfDpJ3wLGpJQetpGXubDou','Test User','2026-07-28 07:51:25','2026-07-28 07:51:25'),(2,'web_1785318909953@atom.local','$2y$10$ZA.JythcKXnuMCf6RXpx.uzx0FkufevMGmfbSD63jTpQPyv/VnntC','Web User','2026-07-29 09:55:55','2026-07-29 09:55:55'),(3,'web_1785318929786@atom.local','$2y$10$EpuYiRK.I6ffeNXGiqWOnOO0aIDWa1Rbf5dN0FiTXglW1ymXbSGEW','Web User','2026-07-29 09:55:56','2026-07-29 09:55:56'),(4,'web_1785318931740@atom.local','$2y$10$6ZpNL0mOnPozr2/mYwAvvOSCID5u1aJlb5gu55qXHVGnluzg953Mq','Web User','2026-07-29 09:55:56','2026-07-29 09:55:56'),(5,'web_1785739309550@atom.local','$2y$10$E7XNGbJDRr8JuJsJ29KgdupCoZQrf7XXQJNgVxnre0nebPeAbSrhO','Web User','2026-08-03 06:41:50','2026-08-03 06:41:50'),(6,'verify@atom.test','$2y$10$RjgBceMGJ9fS1jvmamuRP.oCFeyD9uo4O6tLHuEFuetMi00mbw3Gu','Verify','2026-08-08 17:55:40','2026-08-08 17:55:40');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-09  2:47:43
