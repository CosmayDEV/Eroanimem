
export type UserRole = 'USER' | 'MODERATOR' | 'FANSUB' | 'ADMIN';

export interface User {
  id: string;
  username: string;
  email: string;
  password?: string;
  role: UserRole;
  avatar: string;
  xp: number;
  level: number;
  readingList: string[];
  watchList: string[];
  bio?: string;
  statusNote?: string;
  banner?: string;
  createdAt: number;
}

export type Media = MediaItem;

export interface SiteSettings {
  popupHtml: string;
  popupLink: string;
  isPopupActive: boolean;
}

export interface StatusNote {
  id: string;
  userId: string;
  username: string;
  avatar: string;
  content: string;
  createdAt: number;
}

export interface Comment {
  id: string;
  mediaId: string;
  userId: string;
  username: string;
  avatar: string;
  userRole: UserRole;
  content: string;
  likes: number;
  dislikes: number;
  replies: Comment[];
  createdAt: number;
}

export interface Chapter {
  id: string;
  number: number;
  title: string;
  uploadDate: string;
  pages: string[];
}

export interface Episode {
  id: string;
  number: number;
  title: string;
  thumbnail?: string;
  uploadDate: string;
  videoUrl: string;
  sourceType: 'embed' | 'direct';
}

export type MediaType = 'MANGA' | 'ANIME';

export interface MediaItem {
  id: string;
  type: 'MANGA' | 'ANIME';
  title: string;
  altTitle?: string;
  coverImage: string;
  bannerImage: string;
  description: string;
  genres: string[];
  status: 'Devam Ediyor' | 'Tamamlandı';
  rating: number;
  author?: string;
  studio?: string;
  views: number;
  chapters?: Chapter[];
  episodes?: Episode[];
  isAdult?: boolean;
  addedBy?: string;
}

export interface FilterState {
  category?: string;
  type?: 'MANGA' | 'ANIME';
  query?: string;
}

export type ViewState = 'HOME' | 'DETAILS' | 'READER' | 'PLAYER' | 'ADMIN_PANEL' | 'FANSUB_PANEL' | 'PROFILE' | 'AUTH' | 'BROWSE';
