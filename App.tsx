
import React, { useState, useEffect } from 'react';
import Navbar from './components/Navbar';
import HeroSlider from './components/HeroSlider';
import Section from './components/Section';
import MangaCard from './components/MangaCard';
import MangaDetail from './components/MangaDetail';
import AnimeDetail from './components/AnimeDetail';
import MangaReader from './components/MangaReader';
import VideoPlayer from './components/VideoPlayer';
import AdminPanel from './components/AdminPanel';
import FansubPanel from './components/FansubPanel';
import ProfileView from './components/ProfileView';
import StatusNotes from './components/StatusNotes';
import CommentSection from './components/CommentSection';
import AuthModal from './components/AuthModal';
import AgeGate from './components/AgeGate';
import ViewRouter from './components/ViewRouter';
import AdSafeGuard from './components/AdSafeGuard';
import { AuthService } from './services/authService';
import { MediaService } from './services/mediaService';
import { SettingsService } from './services/settingsService';
import { MediaItem, Chapter, Episode, ViewState, User, StatusNote, Comment, SiteSettings, FilterState } from './types';

function App() {
  const [view, setView] = useState<ViewState>('HOME');
  const [selectedMedia, setSelectedMedia] = useState<MediaItem | null>(null);
  const [selectedChapter, setSelectedChapter] = useState<Chapter | null>(null);
  const [selectedEpisode, setSelectedEpisode] = useState<Episode | null>(null);
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [allUsers, setAllUsers] = useState<User[]>([]);
  const [dynamicContent, setDynamicContent] = useState<MediaItem[]>([]);
  const [filters, setFilters] = useState<FilterState>({});

  const [siteSettings, setSiteSettings] = useState<SiteSettings>({
    popupHtml: 'Premium Eroanimem dünyasına hoş geldiniz!',
    popupLink: '',
    isPopupActive: false
  });

  const [notes, setNotes] = useState<StatusNote[]>([]);

  const [ads, setAds] = useState({
    header: '',
    footer: '',
    sidebar: ''
  });

  const refreshContent = async () => {
    const media = await MediaService.getMedia();
    setDynamicContent(media);
  };

  const refreshUsers = async () => {
    const users = await AuthService.getUsers();
    setAllUsers(users);
  };

  const refreshSettings = async () => {
    const data = await SettingsService.getSettings();
    if (data) {
      setSiteSettings(data.settings);
      setAds(data.ads);
    }
  };

  const handleUpdateSettings = async (newSettings: SiteSettings) => {
    // Optimistic upate
    setSiteSettings(newSettings);
    await SettingsService.saveSettings(newSettings, ads); // Pass current ads
  };

  // Admin panel needs a way to update ads too. 
  // We need to pass a consolidated update function or separate ones.
  // ViewRouter passes onUpdateSettings={setSiteSettings}, which is local state.
  // We need to intercept that in ViewRouter or change App to pass a wrapper.

  // Let's create a combined saved function for AdminPanel use
  const handleAdminSettingsUpdate = async (newSiteSettings: SiteSettings, newAds?: typeof ads) => {
    const updatedAds = newAds || ads;
    setSiteSettings(newSiteSettings);
    setAds(updatedAds);
    await SettingsService.saveSettings(newSiteSettings, updatedAds);
  };

  const handleUpdateUser = async (userId: string, data: Partial<User>) => {
    const updated = await AuthService.updateUser(userId, data);
    if (updated) {
      await refreshUsers();
      if (currentUser?.id === userId) setCurrentUser(updated);
    }
  };

  const handleDeleteUser = async (userId: string) => {
    const success = await AuthService.deleteUser(userId);
    if (success) await refreshUsers();
  };

  useEffect(() => {
    const init = async () => {
      await AuthService.initializeAdmin();
      await refreshUsers();
      const savedUser = localStorage.getItem('ero_current_user');
      if (savedUser) setCurrentUser(JSON.parse(savedUser));
      await refreshContent();
      await refreshSettings();
    };
    init();
  }, []);

  const handleNavigate = (target: ViewState | 'AUTH' | 'MANGA_LIST' | 'ANIME_LIST') => {
    if (target === 'AUTH') {
      if (currentUser) {
        setCurrentUser(null);
        localStorage.removeItem('ero_current_user');
        setView('HOME');
      } else setShowAuthModal(true);
      return;
    }
    if (target === 'MANGA_LIST') { setFilters({ type: 'MANGA' }); setView('BROWSE'); return; }
    if (target === 'ANIME_LIST') { setFilters({ type: 'ANIME' }); setView('BROWSE'); return; }
    setView(target as ViewState);
    window.scrollTo(0, 0);
  };

  const featuredItem = dynamicContent.length > 0 ? dynamicContent[0] : null;

  return (
    <div className="min-h-screen bg-dark-900 pb-20 overflow-x-hidden">
      <AgeGate onConfirm={() => { }} />

      <Navbar
        onSearch={(q) => { setFilters({ query: q }); setView('BROWSE'); }}
        onNavigate={handleNavigate}
        currentUser={currentUser}
        onOpenAuth={() => setShowAuthModal(true)}
        onCategorySelect={(cat) => { setFilters({ category: cat }); setView('BROWSE'); }}
      />

      {ads.header && (view === 'HOME' || view === 'BROWSE') && (
        <div className="pt-24 container mx-auto px-4 md:px-8 flex justify-center">
          <AdSafeGuard className="w-full max-w-5xl overflow-hidden rounded-xl border border-white/5 bg-black/20 p-2" html={ads.header} />
        </div>
      )}

      <ViewRouter
        view={view}
        setView={setView}
        currentUser={currentUser}
        setCurrentUser={setCurrentUser}
        dynamicContent={dynamicContent}
        refreshContent={refreshContent}
        filters={filters}
        setFilters={setFilters}
        selectedMedia={selectedMedia}
        setSelectedMedia={setSelectedMedia}
        selectedChapter={selectedChapter}
        setSelectedChapter={setSelectedChapter}
        selectedEpisode={selectedEpisode}
        setSelectedEpisode={setSelectedEpisode}
        allUsers={allUsers}
        siteSettings={siteSettings}
        setSiteSettings={setSiteSettings}
        notes={notes}
        setNotes={setNotes}
        setShowAuthModal={setShowAuthModal}
        ads={ads}
        onUpdateUser={handleUpdateUser}
        onDeleteUser={handleDeleteUser}
      />

      {showAuthModal && <AuthModal onClose={() => setShowAuthModal(false)} onSuccess={(u) => { setCurrentUser(u); localStorage.setItem('ero_current_user', JSON.stringify(u)); setShowAuthModal(false); }} />}

      {ads.footer && (
        <div className="mt-10 container mx-auto px-4 md:px-8 flex justify-center">
          <AdSafeGuard className="w-full max-w-5xl overflow-hidden rounded-xl border border-white/5 bg-black/20 p-4" html={ads.footer} />
        </div>
      )}

      <footer className="mt-20 border-t border-white/5 pt-12 pb-8 px-8 text-center text-gray-500 bg-black/30">
        <span className="text-white font-black text-2xl italic">Ero<span className="text-brand-500">animem</span></span>
        <p className="text-sm mt-4 font-medium italic uppercase tracking-widest">© 2025 EROANIMEM PREMIUM PLATFORM • ALL RIGHTS RESERVED</p>
      </footer>
    </div>
  );
}

export default App;
