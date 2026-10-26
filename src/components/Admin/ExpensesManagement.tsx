import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Expense } from '../../types';
import {
  DollarSign,
  TrendingUp,
  TrendingDown,
  Plus,
  Trash2,
  Calendar,
  Layers,
  X,
  FileSpreadsheet,
} from 'lucide-react';

export const ExpensesManagement: React.FC = () => {
  const { currentRestaurant, orders, expenses, addExpense, deleteExpense } = useApp();

  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [expenseTitle, setExpenseTitle] = useState('');
  const [expenseAmount, setExpenseAmount] = useState<number>(100000);
  const [expenseCategory, setExpenseCategory] = useState<Expense['category']>('ingredients');
  const [expenseNotes, setExpenseNotes] = useState('');

  const restOrders = orders.filter((o) => o.restaurant_id === currentRestaurant.id && o.status !== 'cancelled');
  const restExpenses = expenses.filter((e) => e.restaurant_id === currentRestaurant.id);

  const totalGrossRevenue = restOrders.reduce((sum, o) => sum + o.total, 0);
  const totalExpenses = restExpenses.reduce((sum, e) => sum + e.amount, 0);
  const netProfit = totalGrossRevenue - totalExpenses;
  const profitMargin = totalGrossRevenue > 0 ? Math.round((netProfit / totalGrossRevenue) * 100) : 0;

  const handleAddExpense = (e: React.FormEvent) => {
    e.preventDefault();
    if (!expenseTitle.trim() || expenseAmount <= 0) return;

    addExpense({
      title: expenseTitle,
      amount: expenseAmount,
      category: expenseCategory,
      date: new Date().toISOString().split('T')[0],
      notes: expenseNotes,
    });

    setExpenseTitle('');
    setExpenseAmount(100000);
    setExpenseNotes('');
    setIsAddModalOpen(false);
  };

  const categoryNames: { [key in Expense['category']]: string } = {
    ingredients: 'مواد ومكونات طعام',
    supplies: 'تغليف ومستلزمات صالة',
    rent: 'إيجار العقار',
    utilities: 'كهرباء، ديزل ومياه',
    salaries: 'رواتب وأجور الطاقم',
    other: 'مصاريف تشغيلية أخرى',
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Financial Analytics Summary KPIs */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-2">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-bold">إجمالي الإيرادات (المبيعات)</span>
            <TrendingUp className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-2xl font-black text-gray-900">
            {totalGrossRevenue.toLocaleString()}{' '}
            <span className="text-xs font-normal text-gray-500">ل.س</span>
          </div>
          <div className="text-[11px] text-gray-400">من كافة الطلبات غير الملغاة</div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-2">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-bold">إجمالي المصاريف المسجلة</span>
            <TrendingDown className="w-4 h-4 text-rose-600" />
          </div>
          <div className="text-2xl font-black text-rose-600">
            {totalExpenses.toLocaleString()}{' '}
            <span className="text-xs font-normal text-gray-500">ل.س</span>
          </div>
          <div className="text-[11px] text-gray-400">{restExpenses.length} بنود مصاريف مدخلة</div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-2">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-bold">صافي الأرباح التشغيلية</span>
            <DollarSign className="w-4 h-4 text-amber-600" />
          </div>
          <div className={`text-2xl font-black ${netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
            {netProfit.toLocaleString()}{' '}
            <span className="text-xs font-normal text-gray-500">ل.س</span>
          </div>
          <div className="text-[11px] text-gray-500 font-semibold">
            هامش الربح التشغيلي: {profitMargin}%
          </div>
        </div>
      </div>

      {/* Expenses List & Add Action */}
      <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h3 className="text-lg font-black text-gray-900">دفتر المصاريف اليومية</h3>
            <p className="text-xs text-gray-500">
              تسجيل التوريدات، المشتريات والرواتب ومصاريف التشغيل للمطعم.
            </p>
          </div>

          <button
            onClick={() => setIsAddModalOpen(true)}
            className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
          >
            <Plus className="w-4 h-4" />
            <span>تسجيل مصروف جديد</span>
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-xs text-right">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200 text-gray-500 font-bold">
                <th className="py-3 px-4">التاريخ</th>
                <th className="py-3 px-4">بيان المصروف</th>
                <th className="py-3 px-4">التصنيف</th>
                <th className="py-3 px-4">المبلغ</th>
                <th className="py-3 px-4 text-center">حذف</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {restExpenses.map((exp) => (
                <tr key={exp.id} className="hover:bg-gray-50/80 transition-colors">
                  <td className="py-3 px-4 font-mono text-gray-600">{exp.date}</td>
                  <td className="py-3 px-4">
                    <div className="font-bold text-gray-900">{exp.title}</div>
                    {exp.notes && <div className="text-[10px] text-gray-400">{exp.notes}</div>}
                  </td>
                  <td className="py-3 px-4">
                    <span className="bg-gray-100 text-gray-800 px-2 py-0.5 rounded-md font-semibold text-[11px]">
                      {categoryNames[exp.category] || exp.category}
                    </span>
                  </td>
                  <td className="py-3 px-4 font-bold text-rose-600 text-sm">
                    {exp.amount.toLocaleString()} ل.س
                  </td>
                  <td className="py-3 px-4 text-center">
                    <button
                      onClick={() => {
                        if (window.confirm(`هل أنت متأكد من حذف مصروف "${exp.title}"؟`)) {
                          deleteExpense(exp.id);
                        }
                      }}
                      className="text-gray-400 hover:text-rose-600 p-1 cursor-pointer"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add Expense Modal */}
      {isAddModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-gray-100 space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-sm text-gray-900">تسجيل مصروف جديد</h3>
              <button onClick={() => setIsAddModalOpen(false)} className="p-1 rounded-full text-gray-400 hover:bg-gray-100">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddExpense} className="space-y-3 text-xs">
              <div>
                <label className="font-bold text-gray-700 block mb-1">بيان المصروف:</label>
                <input
                  type="text"
                  required
                  value={expenseTitle}
                  onChange={(e) => setExpenseTitle(e.target.value)}
                  placeholder="مثال: فاتورة لحوم طازجة"
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                />
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">المبلغ (ل.س):</label>
                <input
                  type="number"
                  required
                  min="1"
                  value={expenseAmount}
                  onChange={(e) => setExpenseAmount(Number(e.target.value))}
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 font-mono font-bold"
                />
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">نوع المصروف:</label>
                <select
                  value={expenseCategory}
                  onChange={(e) => setExpenseCategory(e.target.value as Expense['category'])}
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 bg-white"
                >
                  <option value="ingredients">مواد ومكونات طعام</option>
                  <option value="supplies">تغليف ومستلزمات صالة</option>
                  <option value="rent">إيجار العقار</option>
                  <option value="utilities">كهرباء، ديزل ومياه</option>
                  <option value="salaries">رواتب وأجور الطاقم</option>
                  <option value="other">مصاريف تشغيلية أخرى</option>
                </select>
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">ملاحظات إضافية:</label>
                <input
                  type="text"
                  value={expenseNotes}
                  onChange={(e) => setExpenseNotes(e.target.value)}
                  placeholder="رقم الفاتورة أو المورد..."
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsAddModalOpen(false)}
                  className="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-100"
                >
                  إلغاء
                </button>
                <button
                  type="submit"
                  className="bg-amber-600 text-white font-bold px-4 py-1.5 rounded-lg cursor-pointer"
                >
                  حفظ المصروف
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
