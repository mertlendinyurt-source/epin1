'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Send, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';

const categories = [
  { value: 'odeme', label: 'Ödeme', description: 'Ödeme sorunları, faturalandırma' },
  { value: 'teslimat', label: 'Teslimat', description: 'UC teslimat sorunları' },
  { value: 'hesap', label: 'Hesap', description: 'Hesap ayarları, giriş sorunları' },
  { value: 'diger', label: 'Diğer', description: 'Diğer konular ve sorular' }
];

export default function NewSupportTicket() {
  const router = useRouter();
  const [formData, setFormData] = useState({
    subject: '',
    category: '',
    message: ''
  });
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});

  const validateForm = () => {
    const newErrors = {};
    if (!formData.subject || formData.subject.length < 5) {
      newErrors.subject = 'Konu en az 5 karakter olmalıdır';
    }
    if (!formData.category) {
      newErrors.category = 'Lütfen bir kategori seçin';
    }
    if (!formData.message || formData.message.length < 10) {
      newErrors.message = 'Mesaj en az 10 karakter olmalıdır';
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    const token = localStorage.getItem('userToken');
    if (!token) {
      router.push('/');
      return;
    }

    setSubmitting(true);
    try {
      const response = await fetch('/api/support/tickets', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();

      if (response.status === 429) {
        toast.error('Çok fazla talep oluşturdunuz. Lütfen biraz bekleyin.');
        return;
      }

      if (data.success) {
        toast.success('Destek talebiniz oluşturuldu!');
        router.push(`/account/support/${data.data.id}`);
      } else {
        toast.error(data.error || 'Talep oluşturulamadı');
      }
    } catch (error) {
      console.error('Submit error:', error);
      toast.error('Bir hata oluştu');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link 
          href="/account/support"
          className="p-2 rounded-lg hover:bg-white/10 text-white/60 hover:text-white transition-colors"
        >
          <ArrowLeft className="w-5 h-5" />
        </Link>
        <div>
          <h1 className="text-2xl font-bold text-white">Yeni Destek Talebi</h1>
          <p className="text-white/60 mt-1">Size nasıl yardımcı olabiliriz?</p>
        </div>
      </div>

      {/* Form */}
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="bg-[#1e2229] rounded-xl border border-white/10 p-6 space-y-6">
          {/* Subject */}
          <div className="space-y-2">
            <Label className="text-white">Konu *</Label>
            <Input
              value={formData.subject}
              onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
              placeholder="Talebinizi kısaca özetleyin"
              className="bg-[#12151a] border-white/10 text-white placeholder:text-white/40 focus:border-blue-500"
            />
            {errors.subject && (
              <p className="text-red-400 text-sm">{errors.subject}</p>
            )}
          </div>

          {/* Category */}
          <div className="space-y-2">
            <Label className="text-white">Kategori *</Label>
            <div className="grid grid-cols-2 gap-3">
              {categories.map((cat) => (
                <button
                  key={cat.value}
                  type="button"
                  onClick={() => setFormData({ ...formData, category: cat.value })}
                  className={`p-4 rounded-lg border text-left transition-all ${
                    formData.category === cat.value
                      ? 'border-blue-500 bg-blue-500/10'
                      : 'border-white/10 bg-[#12151a] hover:border-white/20'
                  }`}
                >
                  <div className="font-medium text-white">{cat.label}</div>
                  <div className="text-xs text-white/50 mt-1">{cat.description}</div>
                </button>
              ))}
            </div>
            {errors.category && (
              <p className="text-red-400 text-sm">{errors.category}</p>
            )}
          </div>

          {/* Message */}
          <div className="space-y-2">
            <Label className="text-white">Mesajınız *</Label>
            <textarea
              value={formData.message}
              onChange={(e) => setFormData({ ...formData, message: e.target.value })}
              placeholder="Sorununuzu veya sorunuzu detaylı bir şekilde açıklayın..."
              rows={6}
              className="w-full px-4 py-3 rounded-lg bg-[#12151a] border border-white/10 text-white placeholder:text-white/40 focus:border-blue-500 focus:outline-none resize-none"
            />
            {errors.message && (
              <p className="text-red-400 text-sm">{errors.message}</p>
            )}
          </div>
        </div>

        {/* Info Box */}
        <div className="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
          <p className="text-blue-400 text-sm">
            <strong>Not:</strong> Talebiniz oluşturulduktan sonra, ekibimiz en kısa sürede yanıt verecektir. 
            Yanıt gelene kadar yeni mesaj gönderemezsiniz.
          </p>
        </div>

        {/* Submit Button */}
        <div className="flex justify-end gap-3">
          <Link href="/account/support">
            <Button type="button" variant="outline" className="border-white/10 text-white hover:bg-white/10">
              İptal
            </Button>
          </Link>
          <Button 
            type="submit" 
            disabled={submitting}
            className="bg-blue-600 hover:bg-blue-700 text-white"
          >
            {submitting ? (
              <>
                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                Gönderiliyor...
              </>
            ) : (
              <>
                <Send className="w-4 h-4 mr-2" />
                Talep Oluştur
              </>
            )}
          </Button>
        </div>
      </form>
    </div>
  );
}
