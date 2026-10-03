
import { MediaItem } from './types';


// Eğer site "eroanimem.xyz" gibi bir yerdeyse (Production), API aynı domaindedir (/api.php).
// Eğer "localhost"ta çalışıyorsak (Development), yerel sunucuya git.
export const API_URL = import.meta.env.PROD
  ? '/api.php'
  : 'http://localhost/eroanimem/api.php';

export const MOCK_MANGA: MediaItem[] = [];
export const MOCK_ANIME: MediaItem[] = [];
export const ALL_CONTENT: MediaItem[] = [];

export const GENRES = [
  'Aksiyon', 'Hentai', 'Romantizm', 'Komedi', 'Dram', 'Fantastik',
  'Korku', 'Gizem', 'Sci-Fi', 'Yetişkin', 'Spor', 'Doğaüstü'
];

export const CATEGORIES = [
  "Live Action", "JAV", "Sansürsüz", "Sansürlü", "Ahegao", "Aldatma", "Anal", "Ayak Fetişi",
  "Asansör", "Bakire", "BDSM", "Bondage", "Büyük Memeler", "Canavar", "Cosplay", "Creampie",
  "Dark Skin", "Ecchi", "Elf", "Ensest", "Ev Hanımı", "Fantastik", "Fetiş", "Futanari",
  "Gang Bang", "Genç", "Gerilim", "Hamile", "Harem", "Hastane", "Hemşire", "Hentai",
  "Hizmetçi", "İsekai", "Jartiyer", "Korku", "Köle", "Küçük Memeler", "Loli", "Mastürbasyon",
  "Manipülasyon", "Milf", "Netorare", "Psikolojik", "Ofis", "Okul", "Oral", "Oyun",
  "Öğrenci", "Öğretmen", "Romantizm", "Sekreter", "Shota", "Succubus", "Süper Güç",
  "Şantaj", "Şeytanlar", "Tecavüz", "Tentacle", "Tren", "Vahşet", "Vampir", "Yaoi",
  "Yuri", "Yüze Oturma", "Türkçe Dublaj"
];

export const YEARS = Array.from({ length: 2025 - 1979 + 1 }, (_, i) => (2025 - i).toString());
