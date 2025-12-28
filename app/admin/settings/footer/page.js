'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';
import { ArrowLeft, Save, Plus, Trash2, GripVertical } from 'lucide-react';

export default function FooterSettingsPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [legalPages, setLegalPages] = useState([]);
  const [settings, setSettings] = useState({
    quickLinks: [
      { label: 'Giriş Yap', action: 'login' },
      { label: 'Kayıt Ol', action: 'register' }
    ],
    categories: [
      { label: 'Dijital Ürün', url: '/' }
    ],
    corporateLinks: []
  });

  useEffect(() => {
    const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken');
    if (!token) {
      router.push('/admin/login');
      return;
    }
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken');
      
      // Load legal pages
      const pagesRes = await fetch('/api/admin/legal-pages', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const pagesData = await pagesRes.json();
      if (pagesData.success) {
        setLegalPages(pagesData.data.filter(p => p.isActive));
      }

      // Load footer settings
      const settingsRes = await fetch('/api/admin/footer-settings', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const settingsData = await settingsRes.json();
      if (settingsData.success && settingsData.data) {
        setSettings(settingsData.data);
      }
    } catch (error) {
      console.error('Load error:', error);
      toast.error('Veriler yüklenemedi');
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken');
      const response = await fetch('/api/admin/footer-settings', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(settings)
      });

      const result = await response.json();
      if (result.success) {
        toast.success('Footer ayarları kaydedildi!');
      } else {
        toast.error(result.error || 'Kaydetme hatası');
      }
    } catch (error) {
      console.error('Save error:', error);
      toast.error('Kaydetme hatası');
    } finally {
      setSaving(false);
    }
  };

  const addCorporateLink = (page) => {
    const exists = settings.corporateLinks.find(l => l.slug === page.slug);
    if (exists) {
      toast.error('Bu sayfa zaten ekli');
      return;
    }
    setSettings({
      ...settings,
      corporateLinks: [...settings.corporateLinks, { label: page.title, slug: page.slug }]
    });
  };

  const removeCorporateLink = (index) => {
    setSettings({
      ...settings,
      corporateLinks: settings.corporateLinks.filter((_, i) => i !== index)
    });
  };

  const moveCorporateLink = (index, direction) => {
    const newLinks = [...settings.corporateLinks];
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= newLinks.length) return;
    [newLinks[index], newLinks[newIndex]] = [newLinks[newIndex], newLinks[index]];
    setSettings({ ...settings, corporateLinks: newLinks });
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <div className="text-white">Yükleniyor...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-950">
      <Toaster position="top-center" richColors />
      
      {/* Header */}
      <div className="bg-slate-900 border-b border-slate-800 py-4 px-6">
        <div className="max-w-5xl mx-auto flex justify-between items-center">
          <div className="flex items-center gap-4">
            <Button 
              variant="ghost" 
              onClick={() => router.push('/admin/dashboard')}
              className="text-slate-400 hover:text-white"
            >
              <ArrowLeft className="w-4 h-4 mr-2" />
              Panel
            </Button>
            <h1 className="text-2xl font-bold text-white">Footer Ayarları</h1>
          </div>
          <Button onClick={handleSave} disabled={saving} className="bg-blue-600 hover:bg-blue-700">
            <Save className="w-4 h-4 mr-2" />
            {saving ? 'Kaydediliyor...' : 'Kaydet'}
          </Button>
        </div>
      </div>

      <div className="max-w-5xl mx-auto p-6 space-y-6">
        {/* Hızlı Erişim */}
        <div className="bg-slate-900 rounded-xl border border-slate-800 p-6">
          <h2 className="text-lg font-semibold text-white mb-4">Hızlı Erişim</h2>
          <p className="text-sm text-slate-500 mb-4">Bu bölüm sabit olarak Giriş Yap ve Kayıt Ol linklerini gösterir.</p>
          <div className="bg-slate-800/50 rounded-lg p-4">
            <div className="space-y-2">
              {settings.quickLinks.map((link, index) => (
                <div key={index} className="flex items-center gap-3 text-slate-300">
                  <span className="w-4 h-4 rounded-full bg-blue-600/20 flex items-center justify-center text-xs text-blue-400">{index + 1}</span>
                  <span>{link.label}</span>
                  <span className="text-slate-500">→ {link.action === 'login' ? 'Auth Modal (Giriş)' : 'Auth Modal (Kayıt)'}</span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Popüler Kategoriler */}
        <div className="bg-slate-900 rounded-xl border border-slate-800 p-6">
          <h2 className="text-lg font-semibold text-white mb-4">Popüler Kategoriler</h2>
          <p className="text-sm text-slate-500 mb-4">Bu bölüm sabit olarak Dijital Ürün linkini gösterir.</p>
          <div className="bg-slate-800/50 rounded-lg p-4">
            <div className="space-y-2">
              {settings.categories.map((cat, index) => (
                <div key={index} className="flex items-center gap-3 text-slate-300">
                  <span className="w-4 h-4 rounded-full bg-green-600/20 flex items-center justify-center text-xs text-green-400">{index + 1}</span>
                  <span>{cat.label}</span>
                  <span className="text-slate-500">→ {cat.url}</span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Kurumsal/Künye */}
        <div className="bg-slate-900 rounded-xl border border-slate-800 p-6">
          <h2 className="text-lg font-semibold text-white mb-4">Kurumsal / Künye</h2>
          <p className="text-sm text-slate-500 mb-4">Footer'da gösterilecek kurumsal sayfaları seçin ve sıralayın.</p>
          
          {/* Selected Links */}
          <div className="mb-4">
            <Label className="text-slate-400 mb-2 block">Seçili Sayfalar ({settings.corporateLinks.length})</Label>
            {settings.corporateLinks.length === 0 ? (
              <div className="bg-slate-800/50 rounded-lg p-4 text-center text-slate-500">
                Henüz sayfa seçilmedi. Aşağıdan ekleyin.
              </div>
            ) : (
              <div className="bg-slate-800/50 rounded-lg divide-y divide-slate-700">
                {settings.corporateLinks.map((link, index) => (
                  <div key={index} className="flex items-center gap-3 p-3">
                    <div className="flex flex-col gap-1">
                      <button
                        onClick={() => moveCorporateLink(index, -1)}
                        disabled={index === 0}
                        className="text-slate-500 hover:text-white disabled:opacity-30"
                      >
                        ▲
                      </button>
                      <button
                        onClick={() => moveCorporateLink(index, 1)}
                        disabled={index === settings.corporateLinks.length - 1}
                        className="text-slate-500 hover:text-white disabled:opacity-30"
                      >
                        ▼
                      </button>
                    </div>
                    <span className="text-slate-500 text-sm w-6">{index + 1}.</span>
                    <span className="flex-1 text-white">{link.label}</span>
                    <span className="text-slate-500 text-sm">/legal/{link.slug}</span>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => removeCorporateLink(index)}
                      className="text-red-400 hover:text-red-300"
                    >
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Available Pages */}
          <div>
            <Label className="text-slate-400 mb-2 block">Mevcut Sayfalar</Label>
            {legalPages.length === 0 ? (
              <div className="bg-slate-800/50 rounded-lg p-4 text-center text-slate-500">
                Henüz kurumsal sayfa oluşturulmamış.{' '}
                <button onClick={() => router.push('/admin/content/legal')} className="text-blue-400 hover:underline">
                  Sayfa ekle
                </button>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                {legalPages.map((page) => {
                  const isSelected = settings.corporateLinks.find(l => l.slug === page.slug);
                  return (
                    <button
                      key={page.id}
                      onClick={() => !isSelected && addCorporateLink(page)}
                      disabled={isSelected}
                      className={`flex items-center gap-2 p-3 rounded-lg text-left transition-colors ${
                        isSelected 
                          ? 'bg-green-600/10 border border-green-600/30 text-green-400 cursor-default' 
                          : 'bg-slate-800 hover:bg-slate-700 text-white'
                      }`}
                    >
                      <Plus className={`w-4 h-4 ${isSelected ? 'opacity-0' : ''}`} />
                      <span className="flex-1">{page.title}</span>
                      {isSelected && <span className="text-xs">✓ Eklendi</span>}
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Preview */}
        <div className="bg-slate-900 rounded-xl border border-slate-800 p-6">
          <h2 className="text-lg font-semibold text-white mb-4">Önizleme</h2>
          <div className="bg-[#12151a] rounded-lg p-6">
            <div className="grid grid-cols-3 gap-8 text-sm">
              <div>
                <h3 className="text-white font-semibold mb-3">Hızlı Erişim</h3>
                <ul className="space-y-2">
                  {settings.quickLinks.map((link, i) => (
                    <li key={i} className="text-slate-500">{link.label}</li>
                  ))}
                </ul>
              </div>
              <div>
                <h3 className="text-white font-semibold mb-3">Popüler Kategoriler</h3>
                <ul className="space-y-2">
                  {settings.categories.map((cat, i) => (
                    <li key={i} className="text-slate-500">{cat.label}</li>
                  ))}
                </ul>
              </div>
              <div>
                <h3 className="text-white font-semibold mb-3">Kurumsal/Künye</h3>
                <ul className="space-y-2">
                  {settings.corporateLinks.map((link, i) => (
                    <li key={i} className="text-slate-500">{link.label}</li>
                  ))}
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
