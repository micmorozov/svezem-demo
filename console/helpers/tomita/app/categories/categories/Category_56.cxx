#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 56 - Перевозка плит, блоков, ЖБИ

/*
Перевезти яхту
*/

Transp -> AnyWord<kwtype="перевезти">;
What -> "суда" | "судно" | "яхта" | "катер";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_56);
