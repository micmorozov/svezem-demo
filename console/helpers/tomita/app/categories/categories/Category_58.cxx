#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 58 - Перевозка сейфов и банкоматов

Transp -> AnyWord<kwtype="перевезти">;
What -> "сейф" | "терминал" | "банкомат";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_58);