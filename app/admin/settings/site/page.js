'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';
import { Upload, Image as ImageIcon } from 'lucide-react';

export default function SiteSettingsPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [settings, setSettings] = useState(null);
  
  const [uploads, setUploads] = useState({
    logo: null,
    favicon: null,
    heroImage: null,
    categoryIcon: null
  });

  const [previews, setPreviews] = useState({
    logo: null,
    favicon: null,
    heroImage: null,
    categoryIcon: null
  });

  useEffect(() => {
    checkAuth();
    loadSettings();
  }, []);

  const checkAuth = () => {
    const token = localStorage.getItem('adminToken');
    if (!token) {
      router.push('/admin/login');
    }
  };

  const loadSettings = async () => {
    try {
      const token = localStorage.getItem('adminToken');
      const response = await fetch('/api/admin/settings/site', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (response.status === 401) {
        router.push('/admin/login');
        return;
      }

      const result = await response.json();
      if (result.success) {
        setSettings(result.data);
        setPreviews({
          logo: result.data.logo,
          favicon: result.data.favicon,
          heroImage: result.data.heroImage,
          categoryIcon: result.data.categoryIcon
        });
      }
    } catch (error) {
      console.error('Load settings error:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFileSelect = (e, type) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
      toast.error('Dosya boyutu 2MB\'dan büyük olamaz');
      return;
    }

    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
      toast.error('Geçersiz dosya tipi');
      return;
    }

    setUploads({ ...uploads, [type]: file });

    const reader = new FileReader();
    reader.onloadend = () => {
      setPreviews({ ...previews, [type]: reader.result });
    };
    reader.readAsDataURL(file);
  };

  const handleUploadAndSave = async (type) => {
    const file = uploads[type];
    if (!file) {
      toast.error('Lütfen dosya seçin');
      return;
    }

    setSaving(true);

    try {
      const token = localStorage.getItem('adminToken');
      
      const formData = new FormData();
      formData.append('file', file);
      formData.append('category', type);

      const uploadResponse = await fetch('/api/admin/upload', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      });

      const uploadResult = await uploadResponse.json();

      if (!uploadResult.success) {
        toast.error(uploadResult.error || 'Upload başarısız');
        return;
      }

      const newSettings = {
        ...settings,
        [type]: uploadResult.data.url
      };

      const settingsResponse = await fetch('/api/admin/settings/site', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(newSettings)
      });

      const settingsResult = await settingsResponse.json();

      if (settingsResult.success) {
        setSettings(newSettings);
        setUploads({ ...uploads, [type]: null });
        toast.success(`${typeLabels[type]} başarıyla güncellendi!`);
        
        if (type === 'favicon' || type === 'logo' || type === 'categoryIcon') {
          setTimeout(() => window.location.reload(), 1000);
        }
      } else {
        toast.error('Ayarlar kaydedilemedi');
      }
    } catch (error) {
      console.error('Upload error:', error);
      toast.error(`Yükleme hatası: ${error.message}`);
    } finally {
      setSaving(false);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('adminToken');
    router.push('/admin/login');
  };

  const typeLabels = {
    logo: 'Logo',
    favicon: 'Favicon',
    heroImage: 'Hero Görseli',
    categoryIcon: 'Kategori İkonu'
  };

  if (loading) {
    return (
      <div className=\"min-h-screen bg-slate-950 flex items-center justify-center\">
        <div className=\"text-white text-xl\">Yükleniyor...</div>
      </div>
    );
  }

  return (
    <div className=\"min-h-screen bg-slate-950\">
      <Toaster position=\"top-center\" richColors />

      <div className=\"bg-slate-900 border-b border-slate-800\">
        <div className=\"max-w-7xl mx-auto px-4 sm:px-6 lg:px-8\">
          <div className=\"flex justify-between items-center py-4\">
            <h1 className=\"text-2xl font-bold text-white\">Site Ayarları</h1>
            <div className=\"flex gap-4\">
              <Button onClick={() => router.push('/admin/dashboard')} variant=\"outline\" className=\"border-slate-700 text-white\">
                ← Panel
              </Button>
              <Button onClick={handleLogout} variant=\"outline\" className=\"border-red-700 text-red-500\">
                Çıkış
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div className=\"max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8\">
        <div className=\"grid grid-cols-1 lg:grid-cols-2 gap-6\">
          
          {/* Logo */}
          <UploadCard type=\"logo\" title=\"Logo (Header)\" icon={ImageIcon} color=\"blue\" uploads={uploads} previews={previews} handleFileSelect={handleFileSelect} handleUploadAndSave={handleUploadAndSave} saving={saving} accept=\"image/png,image/svg+xml\" />

          {/* Favicon */}
          <UploadCard type=\"favicon\" title=\"Favicon\" icon={ImageIcon} color=\"green\" uploads={uploads} previews={previews} handleFileSelect={handleFileSelect} handleUploadAndSave={handleUploadAndSave} saving={saving} accept=\"image/png,image/x-icon\" />

          {/* Category Icon */}
          <UploadCard type=\"categoryIcon\" title=\"Kategori İkonu (P Harfi)\" icon={ImageIcon} color=\"yellow\" uploads={uploads} previews={previews} handleFileSelect={handleFileSelect} handleUploadAndSave={handleUploadAndSave} saving={saving} accept=\"image/png,image/jpeg,image/webp\" imageClass=\"w-24 h-24\" />

          {/* Hero */}
          <div className=\"lg:col-span-2\">
            <UploadCard type=\"heroImage\" title=\"Hero / Banner Görseli\" icon={ImageIcon} color=\"purple\" uploads={uploads} previews={previews} handleFileSelect={handleFileSelect} handleUploadAndSave={handleUploadAndSave} saving={saving} accept=\"image/jpeg,image/jpg,image/png,image/webp\" imageClass=\"w-full max-h-64 object-cover\" large />
          </div>
        </div>
      </div>
    </div>
  );
}

function UploadCard({ type, title, icon: Icon, color, uploads, previews, handleFileSelect, handleUploadAndSave, saving, accept, imageClass = \"max-h-24\", large = false }) {
  const colors = {
    blue: 'border-blue-500 bg-blue-600 hover:bg-blue-700',
    green: 'border-green-500 bg-green-600 hover:bg-green-700',
    purple: 'border-purple-500 bg-purple-600 hover:bg-purple-700',
    yellow: 'border-yellow-500 bg-yellow-600 hover:bg-yellow-700'
  };

  return (
    <div className=\"bg-slate-900 rounded-xl p-6 border border-slate-800\">
      <div className=\"flex items-center gap-3 mb-4\">
        <Icon className={`w-6 h-6 text-${color}-400`} />
        <h2 className=\"text-xl font-bold text-white\">{title}</h2>
      </div>

      {previews[type] && (
        <div className=\"mb-4 p-4 bg-slate-800 rounded-lg border border-slate-700\">
          <p className=\"text-sm text-slate-400 mb-2\">Mevcut / Önizleme:</p>
          <img src={previews[type]} alt={title} className={`${imageClass} object-contain bg-white/5 p-2 rounded`} />
        </div>
      )}

      <div className=\"space-y-4\">
        <div>
          <Label className=\"text-slate-300\">Yeni {title} Seç</Label>
          <input type=\"file\" accept={accept} onChange={(e) => handleFileSelect(e, type)} className=\"hidden\" id={`${type}-upload`} />
          <label htmlFor={`${type}-upload`} className={`mt-2 flex items-center justify-center gap-2 px-4 ${large ? 'py-8' : 'py-3'} bg-slate-800 border-2 border-dashed border-slate-700 rounded-lg cursor-pointer hover:${colors[color]} hover:bg-slate-800/70 transition-colors`}>
            <Upload className=\"w-5 h-5 text-slate-400\" />
            <span className=\"text-slate-300\">{uploads[type] ? uploads[type].name : 'Dosya Seç veya Sürükle'}</span>
          </label>
        </div>

        {uploads[type] && (
          <Button onClick={() => handleUploadAndSave(type)} disabled={saving} className={`w-full ${colors[color]}`}>
            {saving ? 'Yükleniyor...' : 'Kaydet ve Uygula'}
          </Button>
        )}
      </div>
    </div>
  );
}
