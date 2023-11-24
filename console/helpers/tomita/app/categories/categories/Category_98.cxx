#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 98 - Аренда Bobcat

Transp -> AnyWord<kwtype="аренда">;
What -> "bobkat" | "бобкэт";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_98);