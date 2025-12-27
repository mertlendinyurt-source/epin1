'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';
import { ArrowLeft, Plus, Pencil, Trash2, Eye, EyeOff, Save, X } from 'lucide-react';

export default function LegalPagesAdmin() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [pages, setPages] = useState([]);
  const [editingPage, setEditingPage] = useState(null);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({
    title: '',
    slug: '',
    content: '',
    effectiveDate: '',
    isActive: true,
    order: 0
  });

  useEffect(() => {
    const token = localStorage.getItem('adminToken');
    if (!token) {
      router.push('/admin/login');
      return;
    }
    loadPages();
  }, []);

  const loadPages = async () => {
    try {
      const token = localStorage.getItem('adminToken');
      const response = await fetch('/api/admin/legal-pages', {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      if (response.status === 401) {
        router.push('/admin/login');
        return;
      }

      const result = await response.json();
      if (result.success) {
        setPages(result.data || []);
      }
    } catch (error) {
      console.error('Load error:', error);
      toast.error('Sayfalar yüklenemedi');
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = () => {
    setEditingPage('new');
    setFormData({
      title: '',
      slug: '',
      content: '',
      effectiveDate: new Date().toISOString().split('T')[0],
      isActive: true,
      order: pages.length
    });
  };

  const handleEdit = (page) => {
    setEditingPage(page.id);
    setFormData({
      title: page.title,
      slug: page.slug,
      content: page.content || '',
      effectiveDate: page.effectiveDate ? new Date(page.effectiveDate).toISOString().split('T')[0] : '',
      isActive: page.isActive,
      order: page.order || 0
    });
  };

  const handleCancel = () => {
    setEditingPage(null);
    setFormData({ title: '', slug: '', content: '', effectiveDate: '', isActive: true, order: 0 });
  };

  const generateSlug = (title) => {
    return title
      .toLowerCase()
      .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
      .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
  };

  const handleSave = async () => {
    if (!formData.title || !formData.slug) {
      toast.error('Başlık ve slug zorunludur');
      return;
    }

    setSaving(true);
    try {
      const token = localStorage.getItem('adminToken');
      const isNew = editingPage === 'new';
      
      const response = await fetch(
        isNew ? '/api/admin/legal-pages' : `/api/admin/legal-pages/${editingPage}`,
        {
          method: isNew ? 'POST' : 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(formData)
        }
      );

      const result = await response.json();
      if (result.success) {
        toast.success(isNew ? 'Sayfa oluşturuldu!' : 'Sayfa güncellendi!');
        handleCancel();
        loadPages();
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

  const handleDelete = async (pageId) => {
    if (!confirm('Bu sayfayı silmek istediğinize emin misiniz?')) return;

    try {
      const token = localStorage.getItem('adminToken');
      const response = await fetch(`/api/admin/legal-pages/${pageId}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      });

      const result = await response.json();
      if (result.success) {
        toast.success('Sayfa silindi!');
        loadPages();
      } else {
        toast.error(result.error || 'Silme hatası');
      }
    } catch (error) {
      console.error('Delete error:', error);
      toast.error('Silme hatası');
    }
  };

  const handleToggleActive = async (page) => {
    try {
      const token = localStorage.getItem('adminToken');
      const response = await fetch(`/api/admin/legal-pages/${page.id}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ isActive: !page.isActive })
      });

      const result = await response.json();
      if (result.success) {
        toast.success(page.isActive ? 'Sayfa pasife alındı' : 'Sayfa aktif edildi');
        loadPages();
      }
    } catch (error) {
      console.error('Toggle error:', error);
    }
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
        <div className="max-w-6xl mx-auto flex justify-between items-center">
          <div className="flex items-center gap-4">
            <Button 
              variant="ghost" 
              onClick={() => router.push('/admin/dashboard')}
              className="text-slate-400 hover:text-white"
            >
              <ArrowLeft className="w-4 h-4 mr-2" />
              Panel
            </Button>
            <h1 className="text-2xl font-bold text-white">Kurumsal / Künye Sayfaları</h1>
          </div>
          {!editingPage && (
            <Button onClick={handleCreate} className="bg-blue-600 hover:bg-blue-700">
              <Plus className="w-4 h-4 mr-2" />
              Yeni Sayfa
            </Button>
          )}
        </div>
      </div>

      <div className="max-w-6xl mx-auto p-6">
        {/* Edit Form */}
        {editingPage && (
          <div className="bg-slate-900 rounded-xl border border-slate-800 p-6 mb-6">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-xl font-semibold text-white">
                {editingPage === 'new' ? 'Yeni Sayfa Oluştur' : 'Sayfayı Düzenle'}
              </h2>
              <Button variant="ghost" onClick={handleCancel} className="text-slate-400 hover:text-white">
                <X className="w-4 h-4" />
              </Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <Label className="text-slate-300 mb-2 block">Başlık *</Label>
                <Input
                  value={formData.title}
                  onChange={(e) => {
                    const title = e.target.value;
                    setFormData({ 
                      ...formData, 
                      title,
                      slug: editingPage === 'new' ? generateSlug(title) : formData.slug
                    });
                  }}
                  placeholder="Hizmet Şartları"
                  className="bg-slate-800 border-slate-700 text-white"
                />
              </div>
              <div>
                <Label className="text-slate-300 mb-2 block">Slug *</Label>
                <Input
                  value={formData.slug}
                  onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                  placeholder="hizmet-sartlari"
                  className="bg-slate-800 border-slate-700 text-white"
                />
                <p className="text-xs text-slate-500 mt-1">URL: /legal/{formData.slug || 'slug'}</p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div>
                <Label className="text-slate-300 mb-2 block">Yürürlük Tarihi</Label>
                <Input
                  type="date"
                  value={formData.effectiveDate}
                  onChange={(e) => setFormData({ ...formData, effectiveDate: e.target.value })}
                  className="bg-slate-800 border-slate-700 text-white"
                />
              </div>
              <div>
                <Label className="text-slate-300 mb-2 block">Sıralama</Label>
                <Input
                  type="number"
                  value={formData.order}
                  onChange={(e) => setFormData({ ...formData, order: parseInt(e.target.value) || 0 })}
                  className="bg-slate-800 border-slate-700 text-white"
                />
              </div>
              <div className="flex items-end">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={formData.isActive}
                    onChange={(e) => setFormData({ ...formData, isActive: e.target.checked })}
                    className="w-4 h-4 rounded"
                  />
                  <span className="text-slate-300">Aktif (Yayında)</span>
                </label>
              </div>
            </div>

            <div className="mb-6">
              <Label className="text-slate-300 mb-2 block">İçerik (Markdown)</Label>
              <textarea
                value={formData.content}
                onChange={(e) => setFormData({ ...formData, content: e.target.value })}
                placeholder="# Başlık\n\nParagraf metni...\n\n## Alt Başlık\n\n- Liste öğesi\n- Liste öğesi"
                className="w-full h-80 bg-slate-800 border border-slate-700 rounded-lg p-4 text-white font-mono text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div className="flex gap-3">
              <Button onClick={handleSave} disabled={saving} className="bg-green-600 hover:bg-green-700">
                <Save className="w-4 h-4 mr-2" />
                {saving ? 'Kaydediliyor...' : 'Kaydet'}
              </Button>
              <Button variant="outline" onClick={handleCancel} className="border-slate-700 text-slate-300">
                İptal
              </Button>
            </div>
          </div>
        )}

        {/* Pages List */}
        <div className="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
          <div className="px-6 py-4 border-b border-slate-800">
            <h2 className="text-lg font-semibold text-white">Sayfalar ({pages.length})</h2>
          </div>

          {pages.length === 0 ? (
            <div className="p-8 text-center text-slate-500">
              Henüz sayfa eklenmemiş. "Yeni Sayfa" butonuna tıklayarak başlayın.
            </div>
          ) : (
            <div className="divide-y divide-slate-800">
              {pages.map((page) => (
                <div key={page.id} className="px-6 py-4 hover:bg-slate-800/30 transition-colors">
                  <div className="flex items-center justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-1">
                        <h3 className="font-semibold text-white">{page.title}</h3>
                        <span className={`text-xs px-2 py-0.5 rounded ${page.isActive ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400'}`}>
                          {page.isActive ? 'Aktif' : 'Pasif'}
                        </span>
                      </div>
                      <div className="flex items-center gap-4 text-sm text-slate-500">
                        <span>/legal/{page.slug}</span>
                        {page.effectiveDate && (
                          <span>Yürürlük: {new Date(page.effectiveDate).toLocaleDateString('tr-TR')}</span>
                        )}
                        <span>Güncelleme: {new Date(page.updatedAt).toLocaleDateString('tr-TR')}</span>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleToggleActive(page)}
                        className="text-slate-400 hover:text-white"
                        title={page.isActive ? 'Pasife Al' : 'Aktif Et'}
                      >
                        {page.isActive ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleEdit(page)}
                        className="text-blue-400 hover:text-blue-300"
                      >
                        <Pencil className="w-4 h-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleDelete(page.id)}
                        className="text-red-400 hover:text-red-300"
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
