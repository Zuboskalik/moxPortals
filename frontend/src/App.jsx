import { Route, Routes } from 'react-router-dom';
import Header from './components/layout/Header';
import DashboardPage from './pages/DashboardPage';
import AIWorklogPage from './pages/AIWorklogPage';

export default function App() {
  return (
    <div className="min-h-screen bg-slate-950">
      <Header />
      <main>
        <Routes>
          <Route path="/" element={<DashboardPage />} />
          <Route path="/ai-worklog" element={<AIWorklogPage />} />
        </Routes>
      </main>
    </div>
  );
}
