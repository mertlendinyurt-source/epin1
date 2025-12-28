'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { ArrowLeft, Save, Search, BarChart3, Globe, Code, ExternalLink, Copy, Check } from 'lucide-react';

export default function SEOSettingsPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [settings, setSettings] = useState(null);
  const [showToast, setShowToast] = useState(false);
  const [toastMessage, setToastMessage] = useState('');
  const [toastType, setToastType] = useState('success');
  const [copied, setCopied] = useState('');

  const [formData, setFormData] = useState({
    ga4MeasurementId: '',
    gscVerificationCode: '',
    enableAnalytics: true,
    enableSearchConsole: true
  });

  const BASE_URL = 'https://pinly.com.tr';

  useEffect(() => {
    checkAuth();
    loadSettings();
  }, []);

  const checkAuth = () => {
    const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken');
    if (!token) {
      router.push('/admin/login');
    }
  };

  const loadSettings = async () => {
    try {
      const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken');
      const response = await fetch('/api/admin/settings/seo', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (response.status === 401 || response.status === 403) {
        router.push('/admin/login');
        return;
      }

      const result = await response.json();
      if (result.success && result.data) {
        setSettings(result.data);
        setFormData({
          ga4MeasurementId: result.data.ga4MeasurementId || '',
          gscVerificationCode: result.data.gscVerificationCode || '',
          enableAnalytics: result.data.enableAnalytics !== false,
          enableSearchConsole: result.data.enableSearchConsole !== false
        });
      }
    } catch (error) {
      console.error('Failed to load settings:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);

    try {
      const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken');
      const response = await fetch('/api/admin/settings/seo', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(formData)
      });

      const result = await response.json();
      
      if (result.success) {
        toast('SEO ayarları başarıyla kaydedildi!', 'success');
        loadSettings();
      } else {
        toast(result.error || 'Kaydetme hatası', 'error');
      }
    } catch (error) {
      console.error('Save error:', error);
      toast('Kaydetme hatası', 'error');
    } finally {
      setSaving(false);
    }
  };

  const toast = (message, type = 'success') => {
    setToastMessage(message);
    setToastType(type);
    setShowToast(true);
    setTimeout(() => setShowToast(false), 3000);
  };

  const copyToClipboard = (text, key) => {
    navigator.clipboard.writeText(text);
    setCopied(key);
    setTimeout(() => setCopied(''), 2000);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-900 flex items-center justify-center">
        <div className="text-white text-xl">Yükleniyor...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-900">
      {/* Toast */}
      {showToast && (
        <div className="fixed top-4 right-4 z-50">
          <div className={`px-6 py-3 rounded-lg shadow-lg ${
            toastType === 'success' ? 'bg-green-600' : 'bg-red-600'
          } text-white`}>
            {toastMessage}
          </div>
        </div>
      )}

      {/* Header */}
      <div className="bg-gray-800 border-b border-gray-700">
        <div className="max-w-6xl mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <button
                onClick={() => router.push('/admin/dashboard')}
                className="p-2 hover:bg-gray-700 rounded-lg transition-colors"
              >
                <ArrowLeft className="w-5 h-5 text-gray-400" />
              </button>
              <div>
                <h1 className="text-xl font-bold text-white">SEO & Analytics Ayarları</h1>
                <p className="text-sm text-gray-400">Google Analytics ve Search Console entegrasyonu</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-4xl mx-auto px-4 py-8">
        {/* Quick Links */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div className="flex items-center gap-3 mb-2">
              <Globe className="w-5 h-5 text-blue-400" />
              <span className="text-white font-medium">Sitemap</span>
            </div>
            <div className="flex items-center gap-2">
              <code className="text-xs text-gray-400 flex-1 truncate">{BASE_URL}/sitemap.xml</code>
              <button
                onClick={() => copyToClipboard(`${BASE_URL}/sitemap.xml`, 'sitemap')}
                className="p-1 hover:bg-gray-700 rounded"
              >
                {copied === 'sitemap' ? <Check className="w-4 h-4 text-green-400" /> : <Copy className="w-4 h-4 text-gray-400" />}
              </button>
              <a href="/sitemap.xml" target="_blank" className="p-1 hover:bg-gray-700 rounded">
                <ExternalLink className="w-4 h-4 text-gray-400" />
              </a>
            </div>
          </div>

          <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div className="flex items-center gap-3 mb-2">
              <Code className="w-5 h-5 text-green-400" />
              <span className="text-white font-medium">Robots.txt</span>
            </div>
            <div className="flex items-center gap-2">
              <code className="text-xs text-gray-400 flex-1 truncate">{BASE_URL}/robots.txt</code>
              <button
                onClick={() => copyToClipboard(`${BASE_URL}/robots.txt`, 'robots')}
                className="p-1 hover:bg-gray-700 rounded"
              >
                {copied === 'robots' ? <Check className="w-4 h-4 text-green-400" /> : <Copy className="w-4 h-4 text-gray-400" />}
              </button>
              <a href="/robots.txt" target="_blank" className="p-1 hover:bg-gray-700 rounded">
                <ExternalLink className="w-4 h-4 text-gray-400" />
              </a>
            </div>
          </div>

          <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div className="flex items-center gap-3 mb-2">
              <Search className="w-5 h-5 text-purple-400" />
              <span className="text-white font-medium">Rich Results Test</span>
            </div>
            <a 
              href={`https://search.google.com/test/rich-results?url=${encodeURIComponent(BASE_URL)}`}
              target="_blank"
              className="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1"
            >
              Schema Test <ExternalLink className="w-3 h-3" />
            </a>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Google Analytics Section */}
          <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div className="flex items-center gap-3 mb-4">
              <BarChart3 className="w-6 h-6 text-orange-400" />
              <h2 className="text-lg font-semibold text-white">Google Analytics 4</h2>
            </div>

            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="enableAnalytics"
                  checked={formData.enableAnalytics}
                  onChange={(e) => setFormData({ ...formData, enableAnalytics: e.target.checked })}
                  className="w-4 h-4 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500"
                />
                <label htmlFor="enableAnalytics" className="text-white">Analytics aktif</label>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-300 mb-2">
                  GA4 Measurement ID
                </label>
                <input
                  type="text"
                  value={formData.ga4MeasurementId}
                  onChange={(e) => setFormData({ ...formData, ga4MeasurementId: e.target.value })}
                  className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                  placeholder="G-XXXXXXXXXX"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Google Analytics 4 &gt; Admin &gt; Data Streams &gt; Web &gt; Measurement ID
                </p>
              </div>

              <div className="bg-gray-900/50 rounded-lg p-4">
                <p className="text-sm text-gray-400 mb-2">Otomatik izlenen event'ler:</p>
                <div className="flex flex-wrap gap-2">
                  {['page_view', 'view_item', 'begin_checkout', 'purchase', 'login', 'sign_up'].map(event => (
                    <span key={event} className="px-2 py-1 bg-gray-700 rounded text-xs text-gray-300">
                      {event}
                    </span>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Google Search Console Section */}
          <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div className="flex items-center gap-3 mb-4">
              <Search className="w-6 h-6 text-blue-400" />
              <h2 className="text-lg font-semibold text-white">Google Search Console</h2>
            </div>

            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="enableSearchConsole"
                  checked={formData.enableSearchConsole}
                  onChange={(e) => setFormData({ ...formData, enableSearchConsole: e.target.checked })}
                  className="w-4 h-4 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500"
                />
                <label htmlFor="enableSearchConsole" className="text-white">Search Console doğrulaması aktif</label>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-300 mb-2">
                  HTML Meta Verification Code
                </label>
                <input
                  type="text"
                  value={formData.gscVerificationCode}
                  onChange={(e) => setFormData({ ...formData, gscVerificationCode: e.target.value })}
                  className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 font-mono text-sm"
                  placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Search Console &gt; Settings &gt; Ownership verification &gt; HTML tag &gt; content değeri
                </p>
              </div>

              <div className="bg-blue-900/20 border border-blue-700/50 rounded-lg p-4">
                <p className="text-sm text-blue-300 mb-2">Sitemap URL'nizi Search Console'a ekleyin:</p>
                <div className="flex items-center gap-2">
                  <code className="flex-1 bg-gray-900 px-3 py-2 rounded text-sm text-white">
                    {BASE_URL}/sitemap.xml
                  </code>
                  <button
                    type="button"
                    onClick={() => copyToClipboard(`${BASE_URL}/sitemap.xml`, 'gsc-sitemap')}
                    className="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                  >
                    {copied === 'gsc-sitemap' ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Schema.org Info */}
          <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div className="flex items-center gap-3 mb-4">
              <Code className="w-6 h-6 text-green-400" />
              <h2 className="text-lg font-semibold text-white">Schema.org (Otomatik)</h2>
            </div>
            <p className="text-gray-400 text-sm mb-4">
              Aşağıdaki schema'lar otomatik olarak tüm sayfalara eklenir:
            </p>
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 bg-green-400 rounded-full"></span>
                <span className="text-gray-300">Organization Schema</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 bg-green-400 rounded-full"></span>
                <span className="text-gray-300">WebSite Schema</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 bg-green-400 rounded-full"></span>
                <span className="text-gray-300">Product Schema (ürün sayfalarında)</span>
              </div>
            </div>
          </div>

          {/* Submit Button */}
          <button
            type="submit"
            disabled={saving}
            className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-600"
          >
            <Save className="w-5 h-5" />
            {saving ? 'Kaydediliyor...' : 'Ayarları Kaydet'}
          </button>
        </form>
      </div>
    </div>
  );
}
