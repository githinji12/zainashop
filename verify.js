// verify.js
import bcrypt from 'bcryptjs';

const hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Candidate password to test
const candidates = ['password', '123456', 'letmein']; // try whatever you own

for (const p of candidates) {
  const ok = bcrypt.compareSync(p, hash);
  console.log(`${p} => ${ok ? 'MATCH' : 'no'}`);
}
