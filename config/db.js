// db.js
const mysql = require("mysql2/promise");

const dbConfig = {
  host: "162.241.148.253",
  user: "framequz_hrms",
  password: "ncl037})bCDz",
  database: "framequz_hrms",
};

/*const createUser = async (username, password) => {
  const hashedPassword = await hashPassword(password);
  await db.execute("INSERT INTO users (username, password) VALUES (?, ?)", [
    username,
    hashedPassword,
  ]);
};*/

async function query(sql, params) {
  const connection = await mysql.createConnection(dbConfig);
  try {
    const [results] = await connection.execute(sql, params);
    return results;
  } finally {
    await connection.end();
  }
}

module.exports = {
  query,
};

//module.exports = { db, createUser };
